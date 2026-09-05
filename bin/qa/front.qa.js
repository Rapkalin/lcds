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

        // Le hero vaut la hauteur dessinée OU celle de la vue, la plus petite :
        // sans ce second plafond la carte d'appel passait sous la ligne de
        // flottaison sur tout écran de moins de 900px de haut.
        const heroAttendu = Math.min(900, win.innerHeight);
        assert(
            `hauteur du hero = min(900, vue) = ${heroAttendu} (${round(heroBox.height)})`,
            round(heroBox.height) === heroAttendu
        );
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

        // La course doit valoir la somme des visuels, et rien ne doit plafonner
        // leur nombre : plus il y a d'images, plus on défile.
        const cadres = [...rail.querySelectorAll(".carousel__item")];
        const largeurs = cadres.map((node) => node.getBoundingClientRect().width);
        const attendu =
            largeurs.reduce((total, largeur) => total + largeur, 0) +
            (cadres.length - 1) * 12 +
            parseFloat(win.getComputedStyle(rail).paddingRight);

        assert(
            `course = somme des visuels + respiration (${rail.scrollWidth} / ${Math.round(attendu)})`,
            Math.abs(rail.scrollWidth - attendu) < 2
        );
        // Un cadre étroit rempli d'une photo se lit comme un visuel tronqué :
        // le reliquat de 36px de la maquette est une respiration, pas une image.
        assert(
            `aucun visuel étroit (${largeurs.map((l) => Math.round(l)).join("/")})`,
            largeurs.every((largeur) => largeur >= 100)
        );

        rail.scrollTo({ left: rail.scrollWidth, behavior: "auto" });
        await tick();
        const fin = cadres[cadres.length - 1].getBoundingClientRect();
        const bord = rail.getBoundingClientRect().right;
        assert(
            `dernier visuel entier en fin de course (respiration ${Math.round(bord - fin.right)}px)`,
            fin.right <= bord + 1 && Math.round(bord - fin.right) === 36
        );

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
        // Le RYTHME, et non des positions absolues : depuis que le contenu est
        // contribuable, la hauteur d'une entrée dépend de ce qu'on y saisit.
        // Une assertion sur des y absolus mesurait le contenu, pas le CSS —
        // elle est tombée dès que le panneau ouvert de la maquette a disparu du
        // contenu semé. Ce qui doit tenir, c'est 48px de part et d'autre du
        // filet, et aucun retrait aux extrémités.
        const milieu = win.getComputedStyle(items[2]);
        const premier = win.getComputedStyle(items[0]);
        const dernier = win.getComputedStyle(items[items.length - 1]);

        assert(
            `entrée : 48px de part et d'autre du filet (${milieu.paddingTop}/${milieu.paddingBottom})`,
            milieu.paddingTop === "48px" && milieu.paddingBottom === "48px"
        );
        assert(`filet de 1px (${milieu.borderTopWidth})`, milieu.borderTopWidth === "1px");
        assert(
            `première entrée sans filet ni retrait haut (${premier.borderTopWidth}/${premier.paddingTop})`,
            premier.borderTopWidth === "0px" && premier.paddingTop === "0px"
        );
        assert(
            `dernière entrée sans retrait bas (${dernier.paddingBottom})`,
            dernier.paddingBottom === "0px"
        );

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

    // if/else et non un `return` anticipé : la branche desktop rendait la main,
    // et TOUT ce qui suit — géométrie des blocs de fin, campagne
    // d'accessibilité — ne tournait donc qu'aux largeurs mobiles. Constaté en
    // cherchant pourquoi les cotes des technologies n'apparaissaient pas à
    // 1440.
    if (win.innerWidth > 1024) {
        assert(`rendu en mise en page desktop (${win.innerWidth}px > 1024)`, true);
        assert("bouton burger masqué en desktop", styleOf(toggle).display === "none");
        assert("navigation visible en desktop", styleOf(panel).visibility === "visible");
        assert("navigation dans le flux en desktop", styleOf(panel).position === "static");
        assert("liens alignés en ligne", styleOf(list).flexDirection === "row");
    } else {
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
    }

    /* --------------------------------------------------------------------- *
     * Géométrie des deux blocs de fin, relevée au pixel sur le PDF de
     * maquette. Le relevé `get_metadata` de Figma annonçait une ondulation
     * « 0 / 11,4 / 23,4 » : c'étaient les boîtes englobantes de cadres
     * PIVOTÉS. Les trois cartes partagent en réalité leur centre vertical, et
     * seule leur rotation change.
     * --------------------------------------------------------------------- */
    if (win.innerWidth === 1440) {
        const boite = (sel, i = 0) => doc.querySelectorAll(sel)[i]?.getBoundingClientRect() ?? null;
        const cote = (nom, obtenu, attendu, tolerance = 1) => assert(
            `${nom} = ${attendu} (${obtenu === null ? "absent" : obtenu.toFixed(1)})`,
            obtenu !== null && Math.abs(obtenu - attendu) <= tolerance
        );
        const propriete = (sel, i, nom) => {
            const node = doc.querySelectorAll(sel)[i];

            return node ? parseFloat(node.style.getPropertyValue(nom)) : null;
        };

        const railCards = boite(".carousel--cards .carousel__rail");
        cote("techno : étiquette, bord gauche", boite(".block-techno .tag")?.left ?? null, 161);
        cote("techno : bouton d'action, bord droit", boite(".block-techno .cta")?.right ?? null, 1279);
        cote("techno : rail, bord gauche", railCards?.left ?? null, 161);
        cote("techno : rail, plein-bord droit", railCards?.right ?? null, 1440);
        cote("techno : rail, hauteur", railCards?.height ?? null, 494);
        cote("techno : piste, bord gauche", boite(".carousel--cards .carousel__track")?.left ?? null, 161);
        cote("techno : boutons, bord droit", boite(".carousel--cards .carousel__buttons")?.right ?? null, 1279);

        for (const [i, largeur, inclinaison] of [[0, 471.5, 2.88], [1, 447.5, 0], [2, 471.5, -2.88]]) {
            cote(`techno : carte ${i + 1}, largeur`, propriete(".carousel--cards .carousel__item", i, "--item-width"), largeur, 0.1);
            cote(`techno : carte ${i + 1}, inclinaison`, propriete(".carousel--cards .carousel__item", i, "--item-tilt"), inclinaison, 0.01);
        }

        const c0 = boite(".carousel--cards .carousel__item", 0);
        const c1 = boite(".carousel--cards .carousel__item", 1);
        assert(
            `techno : les trois cartes partagent leur centre vertical (${Math.round((c0.top + c0.bottom) / 2)} / ${Math.round((c1.top + c1.bottom) / 2)})`,
            Math.abs((c0.top + c0.bottom) / 2 - (c1.top + c1.bottom) / 2) < 1
        );
        // Le rail impose `overflow-x`, donc `overflow-y: auto` : une carte
        // pivotée plus haute que le rail y ajouterait une barre de défilement
        // verticale, ou verrait ses coins rognés.
        //
        // La hauteur ATTENDUE est calculée, pas relevée : la boîte réellement
        // rendue dépendrait de la préférence de mouvement du navigateur, et une
        // assertion qui change de sens selon un réglage système ne prouve rien.
        const radians = (Math.abs(propriete(".carousel--cards .carousel__item", 0, "--item-tilt")) * Math.PI) / 180;
        const carte = doc.querySelector(".carousel--cards .carousel__item");
        const englobante = carte.offsetHeight * Math.cos(radians)
            + parseFloat(carte.style.getPropertyValue("--item-width")) * Math.sin(radians);
        assert(
            `techno : la boîte de la carte inclinée tient dans le rail (${englobante.toFixed(1)} <= ${railCards.height})`,
            englobante <= railCards.height + 1
        );

        cote("infos : étiquette, bord gauche", boite(".block-info .tag")?.left ?? null, 161);
        cote("infos : visuel, largeur", boite(".block-info__media")?.width ?? null, 440);
        cote("infos : visuel, hauteur", boite(".block-info__media")?.height ?? null, 549);
        cote("infos : colonne de droite, bord gauche", boite(".block-info__entry")?.left ?? null, 726);
        cote("infos : colonne de droite, bord droit", boite(".block-info__entry")?.right ?? null, 1279);
        cote("infos : icône, largeur", boite(".block-info__icon")?.width ?? null, 24);
        cote("infos : texte, bord gauche", boite(".block-info__head")?.left ?? null, 774);
        cote("infos : bouton contourné, bord droit", boite(".block-info .cta--outline")?.right ?? null, 1279);

        const secondeEntree = doc.querySelectorAll(".block-info__entry")[1];
        const styleEntree = styleOf(secondeEntree);
        assert(
            `infos : filet de 1px et 48px au-dessus (${styleEntree.borderTopWidth} / ${styleEntree.paddingTop})`,
            styleEntree.borderTopWidth === "1px" && styleEntree.paddingTop === "48px"
        );
    }

    /* --------------------------------------------------------------------- *
     * Pied de page et sa révélation.
     *
     * La campagne tourne avec `prefers-reduced-motion` forcé : la révélation y
     * est donc DÉSACTIVÉE, et c'est ce qu'on vérifie d'abord — le visuel doit
     * rester visible, sinon il serait inatteignable pour qui refuse les
     * animations. Les maths de la translation sont ensuite éprouvées en posant
     * la classe et l'avancement à la main, ce qui les rend déterministes sans
     * dépendre d'un défilement.
     * --------------------------------------------------------------------- */
    const revele = doc.querySelector("[data-footer-reveal]");

    if (revele !== null) {
        const media = revele.querySelector(".footer-reveal__media");
        const footer = doc.querySelector(".site-footer");
        // La hauteur DÉCOUVERTE est la réserve du bloc, pas la hauteur du
        // visuel : celui-ci est volontairement plus haut, puisqu'il remonte
        // sous les coins arrondis du panneau.
        const hauteur = parseFloat(styleOf(revele).paddingBottom);

        assert(
            "mouvement réduit : la révélation reste désactivée",
            !revele.classList.contains("footer-reveal--animated")
        );
        assert(
            `mouvement réduit : le visuel est découvert (${(media.getBoundingClientRect().bottom - footer.getBoundingClientRect().bottom).toFixed(0)}px)`,
            media.getBoundingClientRect().bottom - footer.getBoundingClientRect().bottom > hauteur - 4
        );
        // Un panneau plus court que le visuel ne pourrait pas le masquer : le
        // mécanisme entier repose sur cet invariant.
        assert(
            `le panneau couvre au moins la hauteur du visuel (${footer.getBoundingClientRect().height.toFixed(0)} >= ${hauteur.toFixed(0)})`,
            footer.getBoundingClientRect().height >= hauteur
        );

        revele.classList.add("footer-reveal--animated");
        revele.style.setProperty("--reveal-progress", "0");
        const couvert = footer.getBoundingClientRect().bottom - media.getBoundingClientRect().bottom;
        assert(`avancement 0 : le panneau recouvre le visuel (écart ${couvert.toFixed(1)}px)`, Math.abs(couvert) < 2);

        revele.style.setProperty("--reveal-progress", "1");
        const decouvert = media.getBoundingClientRect().bottom - footer.getBoundingClientRect().bottom;
        assert(
            `avancement 1 : le visuel est découvert sur ${decouvert.toFixed(0)}px (attendu ${hauteur.toFixed(0)})`,
            Math.abs(decouvert - hauteur) < 4
        );

        // Le cadrage est du CONTENU : les trois valeurs proposées au
        // contributeur doivent produire trois `object-position` distinctes,
        // sinon le choix ne sert à rien.
        const rayonImage = (style) => parseFloat(style.borderBottomLeftRadius);
        const image = media.querySelector(".footer-reveal__image");

        if (image !== null) {
            const classeInitiale = image.className;
            const positions = ["top", "center", "bottom"].map((point) => {
                image.className = `footer-reveal__image is-focus-${point}`;

                return styleOf(image).objectPosition;
            });
            image.className = classeInitiale;

            // Le navigateur NORMALISE les mots-clés — `center top` devient
            // `50% 0%` — et conserve les `calc()`. On raisonne donc sur le
            // décalage RÉSOLU en pixels, pas sur la syntaxe écrite.
            // `cover` prend la PLUS GRANDE des deux échelles : la calculer sur
            // la largeur seule donnait une hauteur fausse dès que la vue
            // devenait étroite, et trois assertions rougissaient à tort.
            const boiteLargeur = image.getBoundingClientRect().width;
            const boite = image.getBoundingClientRect().height;
            const echelle = Math.max(boiteLargeur / image.naturalWidth, boite / image.naturalHeight);
            const hauteurAffichee = image.naturalHeight * echelle;
            // Évaluateur minimal d'`object-position` : Chrome sérialise
            // `min(0px, calc(50% + 32px))` en `min(0px, 50% + 32px)`, et un
            // pourcentage s'y résout contre (boîte - image). Trois assertions
            // rendaient NaN faute de savoir lire cette forme.
            const surplus = boite - hauteurAffichee;
            const terme = (texte) => texte
                .split("+")
                .map((part) => part.trim())
                .reduce((total, part) => total
                    + (part.endsWith("%") ? surplus * (parseFloat(part) / 100) : parseFloat(part)), 0);
            const decalage = (valeur) => {
                const morceau = valeur.split(" ").slice(1).join(" ").trim();
                const enveloppe = /^(min|max)\((.*)\)$/.exec(morceau);

                if (enveloppe !== null) {
                    const valeurs = enveloppe[2].split(",").map(terme);

                    return enveloppe[1] === "min" ? Math.min(...valeurs) : Math.max(...valeurs);
                }

                return terme(morceau.replace(/^calc\((.*)\)$/, "$1"));
            };

            assert(
                `les trois cadrages donnent trois positions distinctes (${positions.join(" | ")})`,
                new Set(positions).size === 3
            );

            const hauts = positions.map(decalage);
            // Aucun cadrage ne doit découvrir l'encoche : le haut de l'image
            // reste au-dessus du haut de la boîte.
            assert(
                `aucun cadrage ne découvre l'encoche (${hauts.map((h) => h.toFixed(0)).join(" | ")})`,
                hauts.every((haut) => haut <= 0.5)
            );

            // La boîte du visuel dépasse d'un rayon ce qu'on découvre : sans
            // compensation, « centre » laissait l'image 32px trop haut par
            // rapport à ce que le visiteur voit — mesuré.
            const rayon = rayonImage(styleOf(footer));
            const debord = hauteurAffichee - boite;
            const centreVu = rayon + (boite - rayon) / 2;
            const ecart = (hauts[1] + hauteurAffichee / 2) - centreVu;

            if (debord >= rayon) {
                assert(
                    `cadrage « centre » : centré sur la PARTIE VUE (écart ${ecart.toFixed(1)}px)`,
                    Math.abs(ecart) < 2
                );
                assert(`cadrage « haut » montre plus haut que « centre » (${hauts[0].toFixed(0)} > ${hauts[1].toFixed(0)})`, hauts[0] > hauts[1]);
                assert(`cadrage « bas » montre plus bas que « centre » (${hauts[2].toFixed(0)} < ${hauts[1].toFixed(0)})`, hauts[2] < hauts[1]);
            } else {
                // Sans débord suffisant, il n'y a rien à recadrer : la seule
                // exigence est de ne pas découvrir l'encoche.
                assert(
                    `débord de ${debord.toFixed(0)}px < rayon : le recadrage est borné à 0 (${hauts[1].toFixed(1)})`,
                    Math.abs(hauts[1]) < 1
                );
            }
        }

        // Le visuel doit remonter SOUS le panneau d'exactement un rayon : les
        // encoches des coins arrondis laissaient sinon voir le fond du bloc.
        // L'arc occupe la bande des `rayon` derniers pixels du panneau — le
        // couvrir entièrement suffit donc, et ça vaut à TOUT avancement,
        // puisque le bas du panneau reste dans la portée du visuel.
        const rayon = parseFloat(styleOf(footer).borderBottomLeftRadius);
        const remonte = footer.getBoundingClientRect().bottom - media.getBoundingClientRect().top;
        assert(
            `le visuel remonte d'un rayon sous le panneau (${remonte.toFixed(0)} pour un rayon de ${rayon})`,
            Math.abs(remonte - rayon) < 2
        );
        assert(
            `le visuel couvre toute la largeur (${media.getBoundingClientRect().width.toFixed(0)} = ${revele.getBoundingClientRect().width.toFixed(0)})`,
            Math.abs(media.getBoundingClientRect().width - revele.getBoundingClientRect().width) < 1
        );

        revele.classList.remove("footer-reveal--animated");
        revele.style.removeProperty("--reveal-progress");
    }

    if (win.innerWidth === 1440) {
        const boitePied = (sel) => doc.querySelector(sel)?.getBoundingClientRect() ?? null;
        const stylePied = styleOf(doc.querySelector(".site-footer"));
        assert(`pied : bloc d'appel à 48 (${boitePied(".footer-call")?.left.toFixed(1)})`,
            Math.abs((boitePied(".footer-call")?.left ?? -1) - 48) <= 1);
        assert(`pied : colonne de droite à 952 (${boitePied(".site-footer__aside")?.left.toFixed(1)})`,
            Math.abs((boitePied(".site-footer__aside")?.left ?? -1) - 952) <= 2);
        assert(`pied : coins arrondis en bas seulement (${stylePied.borderTopLeftRadius} / ${stylePied.borderBottomLeftRadius})`,
            stylePied.borderTopLeftRadius === "0px" && stylePied.borderBottomLeftRadius === "64px");
        assert(`pied : logo de 80 (${boitePied(".site-footer__logo")?.width.toFixed(0)})`,
            Math.abs((boitePied(".site-footer__logo")?.width ?? -1) - 80) <= 1);
    }

    // Le panneau de la carte de technologie suit le MÊME contrat que
    // l'accordéon — bouton `aria-expanded`/`aria-controls` et panneau
    // réellement `hidden` — et le même code JavaScript, sélectionné par
    // `data-disclosure`.
    const carte = doc.querySelector(".tech-card__trigger[aria-expanded='false']");

    if (carte !== null) {
        const panneauCarte = doc.getElementById(carte.getAttribute("aria-controls"));
        carte.click();
        assert(
            "carte de technologie : le clic révèle le texte",
            carte.getAttribute("aria-expanded") === "true"
                && !panneauCarte.hasAttribute("hidden")
                && carte.closest(".tech-card").classList.contains("tech-card--open")
        );
        carte.click();
        assert(
            "carte de technologie : le second clic retire le texte de l'arbre",
            carte.getAttribute("aria-expanded") === "false" && panneauCarte.hasAttribute("hidden")
        );
    }

    /* --------------------------------------------------------------------- *
     * RGAA 10.4 — le texte doit rester lisible à 200 %.
     *
     * Éprouvé à la largeur de référence, qui est ce que demande le critère :
     * il porte sur la taille du texte, pas sur une vue étroite — le palier
     * 320px relève de 10.11, testé séparément à taille de texte normale.
     *
     * Tout est dimensionné en `rem` ici : à 200 %, la mise en page tout entière
     * double. L'en-tête poussait alors la page à 1519px pour une vue de 1440 —
     * mesuré — parce qu'il ne savait pas passer à la ligne.
     * --------------------------------------------------------------------- */
    if (win.innerWidth === 1440) {
        const racine = doc.documentElement;
        const tailleInitiale = racine.style.fontSize;
        racine.style.fontSize = "200%";

        const debordement = racine.scrollWidth > racine.clientWidth + 1;
        const tronques = Array.from(doc.querySelectorAll("body *")).filter((node) => {
            const cs = styleOf(node);

            if (cs.overflow !== "hidden" && cs.overflowY !== "hidden") {
                return false;
            }

            if (node.closest(".screen-reader-text") !== null) {
                return false;
            }

            const texte = Array.from(node.childNodes)
                .filter((enfant) => enfant.nodeType === 3)
                .map((enfant) => enfant.textContent.trim())
                .join("");

            return texte !== "" && node.scrollHeight > node.clientHeight + 2;
        }).length;

        assert(
            `texte à 200 % : pas de défilement horizontal (${racine.scrollWidth} <= ${racine.clientWidth})`,
            !debordement
        );
        assert(`texte à 200 % : aucun texte tronqué (${tronques})`, tronques === 0);

        racine.style.fontSize = tailleInitiale;
    }

    /* --------------------------------------------------------------------- *
     * Accessibilité. Ces assertions verrouillent des défauts CONSTATÉS, pas
     * des précautions : chacune a échoué avant son correctif.
     * --------------------------------------------------------------------- */

    // -- Contraste du texte, calculé sur les styles réels.
    const canal = (c) => {
        const v = c / 255;
        return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    };
    const luminance = ([r, g, b]) => 0.2126 * canal(r) + 0.7152 * canal(g) + 0.0722 * canal(b);
    const contraste = (a, b) => {
        const [haut, bas] = [luminance(a), luminance(b)].sort((x, y) => y - x);
        return (haut + 0.05) / (bas + 0.05);
    };
    const couleur = (valeur) => {
        const trouve = /rgba?\(([^)]+)\)/.exec(valeur || "");

        if (trouve === null) {
            return null;
        }

        const parts = trouve[1].split(/[,\s/]+/).filter(Boolean).map(Number);

        return { rgb: [parts[0], parts[1], parts[2]], a: parts.length > 3 ? parts[3] : 1 };
    };
    const affiche = (node) => {
        const cs = styleOf(node);

        if (cs.display === "none" || cs.visibility === "hidden" || Number(cs.opacity) === 0) {
            return false;
        }

        const box = node.getBoundingClientRect();

        return box.width > 0 && box.height > 0;
    };
    // Le fond effectif : on remonte jusqu'à un aplat opaque. Une image de fond
    // rend la mesure impossible — on écarte plutôt que de deviner.
    const fondDe = (node) => {
        let courant = node;

        while (courant !== null && courant.nodeType === 1) {
            const cs = styleOf(courant);

            if (cs.backgroundImage !== "none") {
                return null;
            }

            const fond = couleur(cs.backgroundColor);

            if (fond !== null && fond.a === 1) {
                return fond.rgb;
            }

            courant = courant.parentElement;
        }

        return [255, 255, 255];
    };

    const insuffisants = [];

    for (const node of doc.querySelectorAll("body *")) {
        const texte = Array.from(node.childNodes)
            .filter((enfant) => enfant.nodeType === 3)
            .map((enfant) => enfant.textContent.trim())
            .join(" ")
            .trim();

        if (texte === "" || !affiche(node) || node.closest(".screen-reader-text") !== null) {
            continue;
        }

        const cs = styleOf(node);
        const avant = couleur(cs.color);
        const fond = fondDe(node);

        if (avant === null || avant.a < 1 || fond === null) {
            continue;
        }

        const taille = parseFloat(cs.fontSize);
        const grand = taille >= 24 || (Number(cs.fontWeight) >= 700 && taille >= 18.66);
        const seuil = grand ? 3 : 4.5;
        const mesure = contraste(avant.rgb, fond);

        if (mesure < seuil) {
            insuffisants.push(`${node.tagName.toLowerCase()} ${mesure.toFixed(2)}:1 < ${seuil}`);
        }
    }

    // Le bouton d'action était à 3,84:1 : blanc sur l'orange de la maquette,
    // 13px. D'où la variante assombrie $orange-on-text.
    assert(
        `contraste du texte (${insuffisants.length} sous le seuil${insuffisants.length === 0 ? "" : " : " + insuffisants.join(", ")})`,
        insuffisants.length === 0
    );

    // -- Prise de focus : chaque contrôle affiché doit apparier :focus-visible.
    const focusables = Array.from(doc.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), select, textarea, summary, [tabindex]:not([tabindex^="-"])'
    )).filter(affiche);
    const sansAnneau = focusables.filter((node) => {
        node.focus({ preventScroll: true });
        const apparie = node.matches(":focus-visible");
        node.blur();

        return !apparie;
    });
    assert(
        `prise de focus visible sur les ${focusables.length} contrôles affichés (${sansAnneau.length} sans anneau)`,
        sansAnneau.length === 0
    );

    // -- Alternatives : l'attribut doit EXISTER sur chaque image, et le thème ne
    // doit plus l'imposer à vide — sinon aucune image du site ne peut être
    // décrite depuis la médiathèque.
    const images = Array.from(doc.querySelectorAll("img"));
    assert(
        `attribut alt présent sur les ${images.length} images`,
        images.every((image) => image.hasAttribute("alt"))
    );

    // -- Plan de titres : un seul h1, aucun saut de niveau.
    const titres = Array.from(doc.querySelectorAll("h1, h2, h3, h4, h5, h6"));
    const niveaux = titres.map((titre) => Number(titre.tagName[1]));
    const sauts = niveaux.filter((niveau, index) => index > 0 && niveau > niveaux[index - 1] + 1);
    assert(`un seul h1 (${niveaux.filter((n) => n === 1).length})`, niveaux.filter((n) => n === 1).length === 1);
    assert(`aucun saut de niveau de titre (${sauts.length})`, sauts.length === 0);
    // Le libellé de section EST le titre : sans lui, les titres d'items se
    // retrouvaient en h2 frères, sans regroupement.
    //
    // On compte les étiquettes restées en <p>, et non celles passées en <h2> :
    // « au moins une en h2 » passait avec deux sections sur trois corrigées —
    // constaté en cassant volontairement une seule des trois.
    assert(
        `aucune étiquette de section restée hors du plan de titres (${doc.querySelectorAll("p.tag").length} en <p>)`,
        doc.querySelectorAll("p.tag").length === 0
    );

    // -- Panneau mobile : la page derrière doit sortir du parcours de tabulation.
    if (affiche(toggle)) {
        toggle.click();

        const horsPanneau = Array.from(doc.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex^="-"])'))
            .filter((node) => !panel.contains(node) && node !== toggle)
            .filter((node) => {
                let courant = node;

                while (courant !== null && courant.nodeType === 1) {
                    const cs = styleOf(courant);

                    // `inert` doit être testé explicitement : il retire du
                    // parcours de tabulation sans toucher display ni visibility.
                    if (cs.display === "none" || cs.visibility === "hidden"
                        || courant.hasAttribute("hidden") || courant.hasAttribute("inert")) {
                        return false;
                    }

                    courant = courant.parentElement;
                }

                return true;
            });

        assert(
            `panneau ouvert : rien de la page derrière n'est tabulable (${horsPanneau.length})`,
            horsPanneau.length === 0
        );

        toggle.click();
        assert(
            "panneau refermé : la page redevient tabulable",
            doc.querySelectorAll("[inert]").length === 0
        );
    }

    return out;
};
