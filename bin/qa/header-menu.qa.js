/**
 * Assertions QA de l'en-tête, jouées dans un navigateur sans interface.
 *
 * La cible est la fenêtre d'un iframe qui charge le site réel : les assertions
 * portent donc sur la page servie et sur son propre JavaScript, pas sur une
 * copie du balisage qui pourrait divorcer avec le thème.
 *
 * Chargé par bin/qa-front.sh, qui relit le <pre id="qa-results"> produit.
 */
window.runHeaderMenuQa = async (win) => {
    const out = [];
    const assert = (name, ok) => out.push(`${ok ? "PASS" : "FAIL"} :: ${name}`);
    const settle = () => new Promise((resolve) => setTimeout(resolve, 350));

    const doc = win.document;
    const styleOf = (node) => win.getComputedStyle(node);

    const toggle = doc.querySelector(".site-header__toggle");
    const panel = doc.getElementById("site-header-nav");
    const list = doc.querySelector(".site-nav__list");

    if (toggle === null || panel === null || list === null) {
        assert("en-tête complet dans la page", false);
        return out;
    }

    assert("logo rendu", doc.querySelector(".site-logo") !== null);
    assert("navigation rendue", doc.querySelectorAll(".site-nav__list a").length > 0);
    assert("bouton d'action rendu", doc.querySelector(".site-header__cta a") !== null);

    // Un débordement horizontal ne se voit pas sur une capture : il se mesure.
    const root = doc.documentElement;
    assert(
        `pas de débordement horizontal (${root.scrollWidth} <= ${root.clientWidth})`,
        root.scrollWidth <= root.clientWidth
    );

    const isOpen = () => toggle.getAttribute("aria-expanded") === "true";
    const hasBodyClass = () => doc.body.classList.contains("is-menu-open");

    // Le hero porte les seules cotes chiffrées de la maquette qui soient
    // vérifiables sans les polices : elles ont attrapé un `box-sizing` manquant,
    // qui faussait toute largeur combinée à un rembourrage.
    const hero = doc.querySelector(".hero");
    const card = doc.querySelector(".hero__card");

    if (hero !== null && card !== null && win.innerWidth === 1440) {
        const heroBox = hero.getBoundingClientRect();
        const cardBox = card.getBoundingClientRect();
        const round = (value) => Math.round(value);

        assert(`hauteur du hero = 900 (${round(heroBox.height)})`, round(heroBox.height) === 900);
        assert(`largeur de la carte = 327 (${round(cardBox.width)})`, round(cardBox.width) === 327);
        assert(
            `carte à 48px du bord droit (${round(win.innerWidth - cardBox.right)})`,
            round(win.innerWidth - cardBox.right) === 48
        );
    }

    if (win.innerWidth > 1024) {
        assert(`rendu en mise en page desktop (${win.innerWidth}px > 1024)`, true);
        assert("bouton burger masqué en desktop", styleOf(toggle).display === "none");
        assert("navigation visible en desktop", styleOf(panel).visibility === "visible");
        assert("navigation dans le flux en desktop", styleOf(panel).position === "static");
        assert("liens alignés en ligne", styleOf(list).flexDirection === "row");
        return out;
    }

    assert(`rendu en mise en page mobile (${win.innerWidth}px <= 1024)`, true);
    assert("bouton burger visible en mobile", styleOf(toggle).display !== "none");
    assert("liens empilés en mobile", styleOf(list).flexDirection === "column");
    assert("état initial fermé", !isOpen() && !hasBodyClass());
    assert("panneau masqué au repos", styleOf(panel).visibility === "hidden");

    toggle.click();
    assert("le clic ouvre", isOpen() && hasBodyClass());
    // Le point qui avait échoué : `visibility` s'anime par paliers et ne doit pas
    // attendre la moitié du fondu pour basculer.
    assert("panneau visible dès l'ouverture", styleOf(panel).visibility === "visible");
    assert("défilement de la page bloqué", styleOf(doc.body).overflow === "hidden");

    doc.dispatchEvent(new win.KeyboardEvent("keydown", { key: "Escape", bubbles: true }));
    assert("échap ferme", !isOpen() && !hasBodyClass());
    assert("panneau encore visible pendant le fondu", styleOf(panel).visibility === "visible");
    await settle();
    assert("panneau masqué après le fondu", styleOf(panel).visibility === "hidden");

    toggle.click();
    const link = panel.querySelector("a");
    link.addEventListener("click", (event) => event.preventDefault());
    link.click();
    assert("le clic sur un lien ferme", !isOpen() && !hasBodyClass());

    return out;
};
