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

    // Section parcours.
    //
    // La campagne force la réduction d'animations : le comportement ATTENDU est
    // donc l'empilement, pas l'épinglage. C'est le repli accessible, et il vaut
    // d'être vérifié — c'est aussi le rendu obtenu sans JavaScript.
    //
    // La mécanique épinglée est éprouvée autrement : on injecte l'avancement et
    // on mesure ce que le CSS en fait. Cela couvre la seule partie où une erreur
    // est probable — les deux formules — sans dépendre du défilement, dont le
    // pilotage n'est pas fiable sous temps virtuel.
    const journey = doc.querySelector("[data-journey]");

    if (journey !== null && win.innerWidth === 1440) {
        const round = (value) => Math.round(value);
        const rect = (selector) => doc.querySelector(selector).getBoundingClientRect();
        const barre = rect(".journey__progress");

        assert(
            "parcours empilé quand les animations sont réduites",
            !journey.classList.contains("journey--pinned")
        );
        assert(`barre : x = 613 (${round(barre.left)})`, round(barre.left) === 613);
        assert(`barre : largeur = 666 (${round(barre.width)})`, round(barre.width) === 666);
        assert(`numéro : x = 613 (${round(rect(".journey__number").left)})`,
            round(rect(".journey__number").left) === 613);
        assert(`corps : x = 726, largeur = 553 (${round(rect(".journey__body").left)}/${round(rect(".journey__body").width)})`,
            round(rect(".journey__body").left) === 726 && round(rect(".journey__body").width) === 553);
        // Hauteur-indépendant : le retrait de section suit la hauteur de la vue,
        // mais l'écart entre la barre et le titre vaut toujours 1 + 48.
        assert(`titre : 49px sous la barre (${round(rect(".journey__title").top - barre.top)})`,
            round(rect(".journey__title").top - barre.top) === 49);
        assert(`six étapes (${doc.querySelectorAll(".journey__step").length})`,
            doc.querySelectorAll(".journey__step").length === 6);

        // Les deux formules, éprouvées sur trois valeurs. Le remplissage vaut
        // (1 + p × 5) / 6, donc 1/6 au départ et non zéro : c'est la maquette.
        journey.classList.add("journey--pinned");

        for (const [progres, remplissage, vues] of [[0, 111, 0], [0.5, 388.5, 2.5], [1, 666, 5]]) {
            journey.style.setProperty("--journey-progress", String(progres));
            assert(
                `avancement ${progres} : remplissage ≈ ${remplissage} (${round(rect(".journey__progress-fill").width)})`,
                Math.abs(rect(".journey__progress-fill").width - remplissage) < 1.5
            );
            assert(
                `avancement ${progres} : rail décalé de ${vues} vue(s) (${round(-rect(".journey__step").left)})`,
                Math.abs(-rect(".journey__step").left - win.innerWidth * vues) < 2
            );
        }

        journey.style.removeProperty("--journey-progress");
        journey.classList.remove("journey--pinned");
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

    // On vérifie la DÉCLARATION, non la fin de l'animation. Attendre qu'une
    // transition s'achève n'est pas déterministe sous temps virtuel — setTimeout
    // y avance instantanément alors que la transition suit les images produites,
    // et l'assertion échouait une fois sur cinq. Ce que le correctif doit
    // garantir, c'est le découplage : `visibility` instantanée à l'ouverture,
    // retardée de la durée du fondu à la fermeture. Les deux assertions
    // ci-dessus en montrent déjà l'effet.
    const ferme = styleOf(panel);
    assert(
        `masquage retardé du fondu (${ferme.transitionProperty} / ${ferme.transitionDelay})`,
        ferme.transitionProperty.includes("visibility") && ferme.transitionDelay.includes("0.2s")
    );

    toggle.click();
    const link = panel.querySelector("a");
    link.addEventListener("click", (event) => event.preventDefault());
    link.click();
    assert("le clic sur un lien ferme", !isOpen() && !hasBodyClass());

    return out;
};
