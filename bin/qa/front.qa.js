/**
 * Assertions QA du front, jouées dans un navigateur sans interface.
 *
 * La cible est la fenêtre d'un iframe qui charge le site réel : les assertions
 * portent donc sur la page servie et sur son propre JavaScript, pas sur une
 * copie du balisage qui pourrait divorcer avec le thème.
 *
 * Chargé par bin/qa-front.sh, qui relit le <pre id="qa-results"> produit.
 */
window.runFrontQa = async (win) => {
    const out = [];
    const assert = (name, ok) => out.push(`${ok ? "PASS" : "FAIL"} :: ${name}`);
    // On interroge l'état jusqu'à ce qu'il arrive, plutôt que d'attendre une
    // durée fixe : la transition dure 200ms, et une attente de 350ms ne laissait
    // que 150ms de marge — dépassée dès que la machine est chargée. La campagne
    // échouait alors une fois sur quatre, ce qui est pire qu'une absence de test.
    const until = async (condition, limite = 40) => {
        for (let essai = 0; essai < limite; essai += 1) {
            if (condition()) {
                return true;
            }

            await new Promise((resolve) => setTimeout(resolve, 25));
        }

        return condition();
    };

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

    // Carrousel : les cotes de la maquette, puis le comportement des boutons.
    // Le défilement est instantané parce que la campagne force la préférence de
    // réduction des animations — sans quoi rien ne serait mesurable au tick près.
    const rail = doc.querySelector(".carousel__rail");

    if (rail !== null && win.innerWidth === 1440) {
        const tick = () => new Promise((resolve) => setTimeout(resolve, 60));
        const thumb = doc.querySelector("[data-carousel-thumb]");
        const previous = doc.querySelector("[data-carousel-prev]");
        const next = doc.querySelector("[data-carousel-next]");
        const offset = () => parseFloat(thumb.style.getPropertyValue("--thumb-offset")) || 0;
        const page = rail.clientWidth;
        const furthest = rail.scrollWidth - rail.clientWidth;
        const railBox = rail.getBoundingClientRect();

        assert(`rail : hauteur = 629 (${Math.round(railBox.height)})`, Math.round(railBox.height) === 629);
        assert(
            `rail : plein-bord droit (${Math.round(railBox.right)} = ${win.innerWidth})`,
            Math.round(railBox.right) === win.innerWidth
        );
        assert("précédent désactivé au repos", previous.disabled === true);
        assert(`curseur à l'origine (${offset().toFixed(1)}%)`, offset() < 0.1);

        next.click();
        await tick();
        assert(`un clic défile d'une page (${Math.round(rail.scrollLeft)}/${page})`,
            Math.abs(rail.scrollLeft - page) < 2);
        assert("précédent réactivé après un clic", previous.disabled === false);

        // Deux clics rapprochés doivent s'ajouter, non se remplacer.
        next.click();
        next.click();
        await tick();
        assert(`clics rapprochés cumulés et bornés (${Math.round(rail.scrollLeft)}/${Math.round(furthest)})`,
            Math.abs(rail.scrollLeft - furthest) < 2);
        assert("suivant désactivé en fin de course", next.disabled === true);

        previous.click();
        await tick();
        assert(`précédent recule d'une page (${Math.round(rail.scrollLeft)})`,
            Math.abs(rail.scrollLeft - (furthest - page)) < 2);

        rail.scrollTo({ left: 0, behavior: "auto" });
        await tick();
    }

    // Accordéon : les cotes de la maquette, puis la bascule des panneaux.
    const items = doc.querySelectorAll(".accordion__item");

    if (items.length > 0 && win.innerWidth === 1440) {
        const round = (value) => Math.round(value);
        const offsetTop = (node) => round(node.getBoundingClientRect().top + win.scrollY);
        const first = items[0].getBoundingClientRect();
        const icon = doc.querySelector(".accordion__icon").getBoundingClientRect();

        assert(`accordéon : 5 entrées (${items.length})`, items.length === 5);
        assert(`colonne : 666 de large (${round(first.width)})`, round(first.width) === 666);
        assert(`bouton : 52 et calé à droite (${round(icon.left)})`,
            round(icon.width) === 52 && round(icon.left) === 1227);
        // La bordure entre dans la boîte : le filet est AU sommet de l'élément.
        assert(`filets aux bons y (${offsetTop(items[1])}, ${offsetTop(items[4])})`,
            offsetTop(items[1]) === 2462 && offsetTop(items[4]) === 3133);

        const closed = [...doc.querySelectorAll(".accordion__trigger")]
            .find((node) => node.getAttribute("aria-expanded") === "false");
        const panel = doc.getElementById(closed.getAttribute("aria-controls"));

        assert("panneau fermé masqué", panel.hidden === true);
        closed.click();
        assert("un clic ouvre le panneau",
            closed.getAttribute("aria-expanded") === "true" && panel.hidden === false);
        closed.click();
        assert("un second clic le referme",
            closed.getAttribute("aria-expanded") === "false" && panel.hidden === true);
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
    assert(
        "panneau masqué après le fondu",
        await until(() => styleOf(panel).visibility === "hidden")
    );

    toggle.click();
    const link = panel.querySelector("a");
    link.addEventListener("click", (event) => event.preventDefault());
    link.click();
    assert("le clic sur un lien ferme", !isOpen() && !hasBodyClass());

    return out;
};
