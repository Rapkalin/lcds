/**
 * CSP campaign: every scenario loads a real page in headless Chrome and reports
 * every Content-Security-Policy violation, every uncaught exception and every
 * console error.
 *
 * The CSP ships as Report-Only: a missing source blocks nothing, it only fires a
 * `securitypolicyviolation` event. That event is the only trace, so it is
 * collected from a script injected BEFORE the page's own.
 *
 * Usage: node bin/qa/csp.qa.mjs <chrome> <scenarios.json>
 * Driven by bin/qa-front.sh, which seeds and restores the data each scenario needs.
 */

import { spawn } from "node:child_process";
import { mkdtempSync, readFileSync, rmSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";

const [chromePath, scenariosPath] = process.argv.slice(2);
const scenarios = JSON.parse(readFileSync(scenariosPath, "utf8"));
const CSP_HEADER = "content-security-policy-report-only";
const WAIT_LIMIT_MS = 15000;

const COLLECTOR = `
    window.__qaViolations = [];
    document.addEventListener("securitypolicyviolation", (event) => {
        window.__qaViolations.push(event.effectiveDirective + " <- " + (event.blockedURI || "inline"));
    });
`;

const delay = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

const launchChrome = () => new Promise((resolve, reject) => {
    const profile = mkdtempSync(join(tmpdir(), "qa-csp-"));
    const chrome = spawn(chromePath, [
        "--headless=new",
        "--disable-gpu",
        "--no-first-run",
        "--no-default-browser-check",
        "--autoplay-policy=no-user-gesture-required",
        "--remote-debugging-port=0",
        `--user-data-dir=${profile}`,
        "about:blank",
    ], { stdio: ["ignore", "ignore", "pipe"] });

    let stderr = "";
    chrome.stderr.on("data", (chunk) => {
        stderr += chunk;
        const match = stderr.match(/DevTools listening on (ws:\/\/\S+)/);

        if (match !== null) {
            resolve({
                endpoint: match[1],
                close: () => new Promise((done) => {
                    chrome.removeAllListeners("exit");
                    chrome.once("exit", () => {
                        // Chrome's helper processes may still be writing to the
                        // profile: a leftover temp dir must not fail the campaign.
                        try {
                            rmSync(profile, { recursive: true, force: true, maxRetries: 5, retryDelay: 200 });
                        } catch {
                            // Left to the OS temp cleanup.
                        }

                        done();
                    });
                    chrome.kill();
                }),
            });
        }
    });
    chrome.on("exit", () => reject(new Error(`Chrome s'est arrêté : ${stderr.slice(-300)}`)));
});

const connect = (endpoint) => new Promise((resolve) => {
    const socket = new WebSocket(endpoint);
    const pending = new Map();
    const listeners = new Set();
    let nextId = 1;

    socket.addEventListener("message", ({ data }) => {
        const message = JSON.parse(data);

        if (message.id !== undefined && pending.has(message.id)) {
            const { resolve: done, reject: fail } = pending.get(message.id);
            pending.delete(message.id);
            message.error === undefined ? done(message.result) : fail(new Error(message.error.message));

            return;
        }

        listeners.forEach((listener) => listener(message));
    });

    const send = (method, params = {}, sessionId = undefined) => new Promise((done, fail) => {
        const id = nextId++;
        pending.set(id, { resolve: done, reject: fail });
        socket.send(JSON.stringify({ id, method, params, sessionId }));
    });

    socket.addEventListener("open", () => resolve({ send, listeners, close: () => socket.close() }));
});

const runScenario = async (cdp, scenario) => {
    const { browserContextId } = await cdp.send("Target.createBrowserContext");
    const { targetId } = await cdp.send("Target.createTarget", { url: "about:blank", browserContextId });
    const { sessionId } = await cdp.send("Target.attachToTarget", { targetId, flatten: true });
    const call = (method, params) => cdp.send(method, params, sessionId);

    const errors = [];
    const documentHeaders = [];
    let isLoaded = false;

    const listener = (message) => {
        if (message.sessionId !== sessionId) {
            return;
        }

        const { method, params } = message;

        if (method === "Page.loadEventFired") {
            isLoaded = true;
        } else if (method === "Runtime.exceptionThrown") {
            errors.push(`exception : ${(params.exceptionDetails.exception?.description ?? params.exceptionDetails.text).split("\n")[0]}`);
        } else if (method === "Runtime.consoleAPICalled" && params.type === "error") {
            errors.push(`console.error : ${params.args.map((arg) => arg.value ?? arg.description).join(" ")}`);
        } else if (method === "Log.entryAdded" && params.entry.level === "error"
            && ! /Content Security Policy/.test(params.entry.text)
            && params.entry.url !== scenario.url) {
            errors.push(`${params.entry.source} : ${params.entry.text} ${params.entry.url ?? ""}`.trim());
        } else if (method === "Network.responseReceived" && params.type === "Document" && params.response.url === scenario.url) {
            documentHeaders.push(Object.keys(params.response.headers).map((name) => name.toLowerCase()));
        }
    };

    cdp.listeners.add(listener);

    await Promise.all([
        call("Page.enable"),
        call("Runtime.enable"),
        call("Network.enable"),
        call("Log.enable"),
    ]);
    await call("Page.addScriptToEvaluateOnNewDocument", { source: COLLECTOR });

    if (scenario.consent !== undefined) {
        await call("Network.setCookie", { name: "tarteaucitron", value: scenario.consent, url: scenario.url });
    }

    await call("Page.navigate", { url: scenario.url });

    for (let waited = 0; ! isLoaded && waited < WAIT_LIMIT_MS; waited += 100) {
        await delay(100);
    }

    const evaluate = async (expression) => (await call("Runtime.evaluate", { expression, returnByValue: true, awaitPromise: true })).result.value;

    // Lets the consent manager fetch its language, services and stylesheet.
    await delay(1500);

    if (scenario.action !== undefined) {
        await evaluate(scenario.action);
    }

    const results = [];
    const assert = (label, isOk, detail = "") => results.push(`${isOk ? "PASS" : "FAIL"} :: ${scenario.label} : ${label}${detail === "" ? "" : ` (${detail})`}`);

    for (const [selector, isExpected] of Object.entries(scenario.expect ?? {})) {
        let isPresent = false;

        for (let waited = 0; waited < WAIT_LIMIT_MS; waited += 250) {
            isPresent = await evaluate(`document.querySelector(${JSON.stringify(selector)}) !== null`);

            if (isPresent === isExpected) {
                break;
            }

            await delay(250);
        }

        assert(`${selector} ${isExpected ? "présent" : "absent"}`, isPresent === isExpected);
    }

    // Lets the YouTube player settle once its frame exists.
    await delay(scenario.settle ?? 0);

    const violations = (await evaluate("window.__qaViolations")) ?? [];

    assert("chargée", isLoaded);
    assert("l'en-tête CSP est posé", documentHeaders.some((headers) => headers.includes(CSP_HEADER)), documentHeaders.length === 0 ? "réponse non vue" : "");
    assert("aucune violation de CSP", violations.length === 0, violations.join(" ; "));
    assert("aucune erreur JavaScript", errors.length === 0, errors.join(" ; "));

    cdp.listeners.delete(listener);
    await cdp.send("Target.disposeBrowserContext", { browserContextId });

    return results;
};

const chrome = await launchChrome();
const cdp = await connect(chrome.endpoint);
let hasFailure = false;

try {
    for (const scenario of scenarios) {
        const results = await runScenario(cdp, scenario);
        results.forEach((line) => console.log(line));
        hasFailure ||= results.some((line) => line.startsWith("FAIL"));
    }
} finally {
    cdp.close();
    await chrome.close();
}

// `exitCode`, not `exit()`: output piped to the shell must be flushed first.
process.exitCode = hasFailure ? 1 : 0;
