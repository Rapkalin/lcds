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
    // Lit une DÉCLARATION dans la feuille de styles. Nécessaire dès que la
    // campagne neutralise l'état qu'on veut vérifier : l'état calculé ne dirait
    // alors rien, la règle si.
    const trouverRegle = (doc, selecteur, extraire) => {
        // Récursif : une règle posée sous `@media` n'apparaît PAS au premier
        // niveau de la feuille. Vérifié — la déclaration de mouvement réduit
        // restait introuvable et l'assertion échouait sur du code correct.
        const fouiller = (liste) => {
            for (const regle of Array.from(liste || [])) {
                // Le sélecteur D'ABORD : depuis l'imbrication CSS, une simple
                // règle de style porte elle aussi un `cssRules` — descendre en
                // premier revenait à ne jamais tester le sélecteur.
                if (typeof regle.selectorText === "string"
                    && regle.selectorText.includes(selecteur)) {
                    const valeur = extraire(regle);

                    if (valeur !== null && valeur !== undefined) {
                        return valeur;
                    }
                }

                if (regle.cssRules !== undefined) {
                    const imbrique = fouiller(regle.cssRules);

                    if (imbrique !== null) {
                        return imbrique;
                    }
                }
            }

            return null;
        };

        for (const feuille of Array.from(doc.styleSheets)) {
            let regles;

            try {
                regles = feuille.cssRules;
            } catch (erreur) {
                continue;
            }

            const trouve = fouiller(regles);

            if (trouve !== null) {
                return trouve;
            }
        }

        return null;
    };

    const styleOf = (node, pseudo) => win.getComputedStyle(node, pseudo || null);

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

        // Le hero fait UNE HAUTEUR D'ÉCRAN, quelle qu'elle soit — demande
        // client, écart assumé avec la maquette qui le dessine en 1440 × 900.
        // Le rapport 16/10 qui donnait cette hauteur laissait voir la section
        // suivante sous lui dès que la vue dépassait 900px.
        assert(
            `hauteur du hero = hauteur de la vue = ${win.innerHeight} (${round(heroBox.height)})`,
            round(heroBox.height) === win.innerHeight
        );

        // La mesure rendue NE SUFFIT PAS, et c'est la mutation qui l'a montré :
        // la campagne joue sur une vue de 900, et l'ancienne écriture
        // `aspect-ratio: 16/10` + `max-height: min(900px, 100svh)` y rend
        // exactement 900 elle aussi. La régression réelle passait donc au
        // travers. On éprouve la RÈGLE, et l'ABSENCE des deux déclarations qui
        // se disputeraient la hauteur.
        const regleHero = trouverRegle(doc, ".hero", (regle) => (
            regle.selectorText === ".hero" && regle.style.height !== "" ? regle.style : null
        ));

        assert(
            "hero : une hauteur d'écran déclarée, sans rapport de forme ni plafond concurrent"
            + ` (${regleHero === null
                ? "règle introuvable"
                : `${regleHero.height}, ratio ${regleHero.aspectRatio || "aucun"},`
                    + ` plafond ${regleHero.maxHeight || "aucun"}`})`,
            regleHero !== null
                && regleHero.height === "100svh"
                && regleHero.aspectRatio === ""
                && regleHero.maxHeight === ""
        );
        assert(`largeur de la carte = 327 (${round(cardBox.width)})`, round(cardBox.width) === 327);
        assert(
            `carte à 48px du bord droit (${round(win.innerWidth - cardBox.right)})`,
            round(win.innerWidth - cardBox.right) === 48
        );
    }

    // En-tête collé : il doit rester visible où que l'on soit dans la page.
    // Mesuré à un endroit où le document est plus haut que la vue, sinon
    // l'assertion ne pourrait pas échouer.
    const header = doc.getElementById("site-header");

    if (header !== null && doc.documentElement.scrollHeight > win.innerHeight + 400) {
        const attente = () => new Promise((resolve) => setTimeout(resolve, 120));
        const position = win.getComputedStyle(header).position;

        assert(
            `en-tête ${hero === null ? "collé" : "fixé au-dessus du hero"} (${position})`,
            hero === null ? position === "sticky" : position === "fixed"
        );

        // L'en-tête reste transparent sur toute la page : la maquette le dessine
        // ainsi, et le fond au défilement a été arbitré contre. Éprouvé, sinon
        // rien n'empêcherait de le réintroduire.
        assert(
            `en-tête transparent (${styleOf(header).backgroundColor} / ${styleOf(header, "::before").backgroundColor})`,
            styleOf(header).backgroundColor === "rgba(0, 0, 0, 0)"
                && styleOf(header, "::before").content === "none"
        );

        // La hauteur est publiée pour les blocs qui doivent l'éviter : la vue
        // épinglée du parcours est collée au même bord que l'en-tête.
        const publiee = parseFloat(
            win.getComputedStyle(doc.documentElement).getPropertyValue("--header-height")
        );
        assert(
            `hauteur publiée = hauteur réelle (${publiee} / ${header.offsetHeight})`,
            Math.abs(publiee - header.offsetHeight) < 1
        );

        // Le défilement est provoqué, PUIS l'évènement est émis à la main.
        // Sous `--virtual-time-budget`, `scrollTo` déplace bien la page — la
        // position lue le confirme — mais Chrome ne délivre pas toujours le
        // `scroll` correspondant : mesuré, zéro évènement pour un retour en
        // haut qui a pourtant eu lieu. Ce qui est éprouvé ici est l'arithmétique
        // du gestionnaire, pas la plomberie évènementielle du navigateur.
        const defiler = async (y) => {
            win.scrollTo(0, y);
            win.dispatchEvent(new win.Event("scroll"));
            await attente();
        };

        await defiler(doc.documentElement.scrollHeight);
        assert(
            `en-tête toujours en haut de la vue, page en bas (défilée de ${Math.round(win.scrollY)}px, en-tête à ${Math.round(header.getBoundingClientRect().top)})`,
            win.scrollY > 400 && Math.abs(header.getBoundingClientRect().top) < 1
        );
        await defiler(0);
    }

    // Comportement des contrôles, éprouvé sur le carrousel QUI EN A.
    //
    // La galerie de l'histoire n'a plus de flèches — elle avance avec le
    // défilement de la page — donc ce bloc vise celui des technologies. Le
    // prendre au premier rail venu appariait le rail de l'histoire aux boutons
    // des technologies, et mesurait un attelage qui n'existe pas.
    //
    // Le défilement est instantané parce que la campagne force la préférence de
    // réduction des animations — sans quoi rien ne serait mesurable au tick près.
    const next = doc.querySelector("[data-carousel-next]");
    const blocAFleches = next === null ? null : next.closest("[data-carousel]");
    const rail = blocAFleches === null ? null : blocAFleches.querySelector(".carousel__rail");

    if (rail !== null && win.innerWidth === 1440) {
        const tick = () => new Promise((resolve) => setTimeout(resolve, 60));
        const thumb = blocAFleches.querySelector("[data-carousel-thumb]");
        const previous = blocAFleches.querySelector("[data-carousel-prev]");
        const offset = () => parseFloat(thumb.style.getPropertyValue("--thumb-offset")) || 0;
        const page = rail.clientWidth;
        const furthest = rail.scrollWidth - rail.clientWidth;

        assert("précédent désactivé au repos", previous.disabled === true);
        assert(`curseur à l'origine (${offset().toFixed(1)}%)`, offset() < 0.1);

        // Une « page » vaut la largeur VISIBLE du rail, et celui-ci est désormais
        // plein-bord des deux côtés : à 1440, une page (1440) dépasse la course
        // restante (1407). Le clic est donc BORNÉ par la fin de course, et
        // l'attendu doit l'être aussi — sinon l'assertion mesure une page
        // théorique que le rail ne peut pas parcourir.
        const pageBornee = Math.min(page, furthest);

        next.click();
        await tick();
        assert(`un clic défile d'une page (${Math.round(rail.scrollLeft)}/${Math.round(pageBornee)})`,
            Math.abs(rail.scrollLeft - pageBornee) < 2);
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
            Math.abs(rail.scrollLeft - Math.max(0, furthest - page)) < 2);

        rail.scrollTo({ left: 0, behavior: "auto" });
        await tick();

        // Glisser-déposer à la souris. Les évènements sont synthétisés : c'est
        // le seul moyen d'éprouver un geste sans périphérique. Le montage ne
        // pose PAS de capture de pointeur, sans quoi rien de tout ceci ne
        // serait jouable — `setPointerCapture` exige un pointeur réellement
        // actif et lève sur un identifiant synthétique.
        const bloc = rail.closest("[data-carousel]");
        // Mesuré ICI et non en tête de bloc : le rail a défilé entre-temps, et
        // sa boîte est la seule chose qui n'ait pas bougé. La déclaration de
        // tête a disparu avec les cotes propres à la galerie de l'histoire,
        // parties dans leur propre bloc.
        const railBox = rail.getBoundingClientRect();
        const milieu = railBox.top + railBox.height / 2;
        const pointeur = (type, x, extra) => rail.dispatchEvent(new win.PointerEvent(type, {
            pointerId: 1,
            pointerType: "mouse",
            button: type === "pointerdown" ? 0 : -1,
            buttons: 1,
            clientX: x,
            clientY: milieu,
            bubbles: true,
            cancelable: true,
            ...(extra || {}),
        }));

        assert("rail débordant annoncé comme tirable", bloc.classList.contains("carousel--draggable"));

        pointeur("pointerdown", 800);
        pointeur("pointermove", 700);
        await tick();
        assert(
            `un glissement de 100px défile de 100px (${Math.round(rail.scrollLeft)})`,
            Math.abs(rail.scrollLeft - 100) < 2
        );
        assert("glissement en cours signalé", bloc.classList.contains("carousel--dragging"));

        // Le clic qui suit le relâchement doit être avalé : sinon un glissement
        // terminé sur le bouton d'une carte en ouvrirait le panneau.
        pointeur("pointerup", 700);
        assert("glissement terminé", !bloc.classList.contains("carousel--dragging"));

        const clicApresGlissement = new win.MouseEvent("click", { bubbles: true, cancelable: true });
        rail.dispatchEvent(clicApresGlissement);
        assert("clic avalé après un glissement", clicApresGlissement.defaultPrevented === true);

        // Sous le seuil, le geste reste un clic : un tremblement de souris ne
        // doit pas empêcher d'ouvrir une carte.
        rail.scrollTo({ left: 0, behavior: "auto" });
        await tick();
        pointeur("pointerdown", 800);
        pointeur("pointermove", 797);
        await tick();
        assert(`sous le seuil, rien ne bouge (${Math.round(rail.scrollLeft)})`, rail.scrollLeft < 1);

        const clicSansGlissement = new win.MouseEvent("click", { bubbles: true, cancelable: true });
        rail.dispatchEvent(clicSansGlissement);
        assert("clic préservé sans glissement", clicSansGlissement.defaultPrevented === false);
        pointeur("pointerup", 797);

        // Au doigt, le défilement natif porte déjà l'inertie : capter le geste
        // ferait surtout perdre le défilement vertical de la page.
        pointeur("pointerdown", 800, { pointerType: "touch" });
        pointeur("pointermove", 700, { pointerType: "touch" });
        await tick();
        assert(`geste tactile laissé au natif (${Math.round(rail.scrollLeft)})`, rail.scrollLeft < 1);
        pointeur("pointerup", 700, { pointerType: "touch" });

        rail.scrollTo({ left: 0, behavior: "auto" });
        await tick();
    }

    /* --------------------------------------------------------------------- *
     * Le rail piloté par le défilement de la page.
     *
     * La campagne force `prefers-reduced-motion` : l'épinglage n'y est donc
     * JAMAIS posé de lui-même, et c'est voulu — c'est la sortie de secours du
     * dispositif. Les assertions ci-dessus mesurent par conséquent le rail dans
     * son comportement natif, celui du repli, et rien n'a eu à changer.
     *
     * L'état épinglé est donc FORCÉ ici : on pose ce que `apply()` pose, puis on
     * éprouve ce qui reste en jeu — l'association que `update()` calcule. C'est
     * la seule chose qu'un réglage de réserve faux casserait en silence.
     * --------------------------------------------------------------------- */
    const reservePin = doc.querySelector("[data-carousel-pin]");

    if (reservePin !== null && win.innerWidth === 1440) {
        const tick = () => new Promise((resolve) => setTimeout(resolve, 60));

        // La mise à jour de l'association est cadencée par
        // `requestAnimationFrame`, et `setTimeout` passe DEVANT l'image en
        // attente sous `--virtual-time-budget` : lire juste après un `tick`
        // donnait un rail encore à zéro, et accusait du code juste.
        //
        // On ATTEND donc la valeur, au lieu de la lire une fois — mais sur un
        // nombre borné de tours. Attendre l'image elle-même a été essayé et
        // rejeté : le `await` ne se dénouait pas et la campagne entière restait
        // suspendue à 1440, sans produire un seul résultat. Un contrôle ne doit
        // jamais pouvoir suspendre la campagne, fût-ce pour dire vrai.
        const attendreRail = async (attendu) => {
            for (let essai = 0; essai < 20; essai += 1) {
                if (Math.abs(railPin.scrollLeft - attendu) < 3) {
                    return railPin.scrollLeft;
                }

                await tick();
            }

            return railPin.scrollLeft;
        };
        const railPin = reservePin.querySelector(".carousel__rail");
        const suivant = reservePin.querySelector("[data-carousel-next]");
        const course = railPin.scrollWidth - railPin.clientWidth;
        const tabAvant = railPin.getAttribute("tabindex");

        // Un seul conteneur d'épinglage sur la page : le carrousel des
        // technologies partage le composant et ne doit PAS en porter.
        assert(
            `épinglage : un seul rail concerné (${doc.querySelectorAll("[data-carousel-pin]").length})`,
            doc.querySelectorAll("[data-carousel-pin]").length === 1
        );
        assert(
            "épinglage : le rail des technologies n'est pas concerné",
            doc.querySelector(".carousel--cards")?.closest("[data-carousel-pin]") === null
        );
        // Au repos — donc sous mouvement réduit — rien n'est posé : le rail
        // garde son défilement natif et son arrêt de tabulation.
        assert(
            `épinglage : inactif sous mouvement réduit (${reservePin.className || "aucune classe"})`,
            ! reservePin.classList.contains("carousel-pin--active")
                && win.getComputedStyle(railPin).overflowX === "auto"
                && tabAvant === "0"
        );

        /* ------------------------------------------------------------------ *
         * LE NOMBRE de la mécanique, lu sur la RÈGLE.
         *
         * La réserve verticale vaut la course horizontale : c'est ce qui donne
         * le rapport de 1:1 relevé sur la référence. Éprouvé sur la déclaration
         * et non sur la hauteur rendue, parce que c'est la campagne elle-même
         * qui doit poser `--pin-course` ci-dessous — le script ne l'active
         * jamais ici, `prefers-reduced-motion` étant forcé.
         *
         * Une assertion sur la hauteur mesurerait donc l'échafaudage du test.
         * Vérifié : elle restait VERTE avec un script qui publiait la course
         * décalée de 100px.
         * ------------------------------------------------------------------ */
        assert(
            "épinglage : la réserve est déclarée égale à la course",
            trouverRegle(doc, ".carousel-pin--active", (regle) => (
                regle.style.height.includes("100svh") && regle.style.height.includes("--pin-course")
                    ? true
                    : null
            )) === true
        );

        // Échafaudage : la campagne pose ce que `apply()` poserait. Ce qui est
        // éprouvé ensuite, c'est le CALCUL de l'association — la seule part qui
        // reste au script une fois l'état posé.
        reservePin.style.setProperty("--pin-course", `${course}px`);
        reservePin.classList.add("carousel-pin--active");
        await tick();

        const verticale = reservePin.offsetHeight - win.innerHeight;

        assert(
            `épinglage : rail non défilable par le geste (${win.getComputedStyle(railPin).overflowX})`,
            win.getComputedStyle(railPin).overflowX === "hidden"
        );

        const hautReserve = reservePin.getBoundingClientRect().top + win.scrollY;
        // Le défilement est provoqué, PUIS l'évènement est émis à la main —
        // même contournement que pour l'en-tête plus haut : sous
        // `--virtual-time-budget`, `scrollTo` déplace la page mais Chrome ne
        // délivre pas toujours le `scroll` correspondant. Sans cette émission,
        // le gestionnaire du rail n'était jamais appelé et le rail restait à
        // zéro sur une association pourtant juste.
        const aller = async (part) => {
            win.scrollTo(0, hautReserve + part * verticale);
            win.dispatchEvent(new win.Event("scroll"));

            return attendreRail(part * course);
        };

        const mi = await aller(0.5);
        const bout = await aller(1);
        const retour = await aller(0);

        assert(
            `épinglage : à mi-réserve, rail à mi-course (${Math.round(mi)} / ${Math.round(course / 2)})`,
            Math.abs(mi - course / 2) < 3
        );
        assert(
            `épinglage : réserve épuisée, rail au bout (${Math.round(bout)} / ${course})`,
            Math.abs(bout - course) < 3
        );
        // Et l'association marche dans les DEUX sens : un calcul qui ne saurait
        // qu'avancer laisserait le rail au bout en remontant la page.
        assert(
            `épinglage : retour à l'origine en remontant (${Math.round(retour)})`,
            retour < 3
        );

        reservePin.classList.remove("carousel-pin--active");
        reservePin.style.removeProperty("--pin-course");
        win.scrollTo(0, 0);
        win.dispatchEvent(new win.Event("scroll"));
        railPin.scrollTo({ left: 0, behavior: "auto" });
        await tick();
    }

    /* --------------------------------------------------------------------- *
     * Le volet : CHAQUE section passe par-dessus la précédente.
     *
     * L'effet est gated sous `prefers-reduced-motion: no-preference`, que la
     * campagne force à l'inverse : la POSITION rendue ne dit donc rien ici, et
     * les deux premières assertions portent sur la RÈGLE.
     *
     * Le décalage de collage est le point à VERROUILLER, et il ne s'écrit pas
     * en CSS : il vaut « hauteur de la vue moins hauteur DE CETTE section ».
     * Un `top: 0` figerait une section plus haute que la vue avant qu'elle
     * soit lue, un `bottom: 0` les empilerait toutes en bas de l'écran dès le
     * premier pixel. Ni l'un ni l'autre ne se voit sur une section courte.
     *
     * La troisième assertion porte sur l'état réel — et c'est la seule qui
     * protège la CONTRIBUTION. Les sections sont réordonnables, et une section
     * sans fond propre laisserait la précédente transparaître. L'aplat de
     * `.main-content` n'y suffit pas, un fond de parent peignant sous ses
     * enfants.
     * --------------------------------------------------------------------- */
    const heroVolet = doc.querySelector(".hero");

    if (heroVolet !== null && win.innerWidth === 1440) {
        assert(
            "volet : le hero est déclaré collé",
            trouverRegle(doc, ".hero", (regle) => (
                regle.style.position === "sticky" && regle.style.top === "0px" ? true : null
            )) === true
        );
        assert(
            "volet : chaque section est déclarée au-dessus de la précédente",
            trouverRegle(doc, ".hero ~ *", (regle) => (
                regle.style.zIndex === "1" && regle.style.position === "relative" ? true : null
            )) === true
        );
        assert(
            "volet : chaque section se colle sur le décalage posé par le script",
            trouverRegle(doc, ".front-page--volet > .hero ~ *", (regle) => (
                regle.style.position === "sticky"
                    && regle.style.top === "var(--volet-top)" ? true : null
            )) === true
        );

        // Le décalage lui-même : le script le pose sur CHAQUE section, et sa
        // valeur doit être « hauteur de la vue moins hauteur de la section ».
        // La campagne force le mouvement réduit, donc la classe est absente et
        // rien n'est collé : c'est la seule chose mesurable ici, et elle suffit
        // à prendre une erreur de signe ou une hauteur lue sur le mauvais nœud.
        const decalages = [];

        for (let noeud = heroVolet.nextElementSibling; noeud !== null; noeud = noeud.nextElementSibling) {
            decalages.push([
                noeud.className.split(" ")[0],
                noeud.style.getPropertyValue("--volet-top").trim(),
                `${win.innerHeight - noeud.offsetHeight}px`,
            ]);
        }

        const faux = decalages.filter(([, pose, attendu]) => pose !== attendu);

        assert(
            `volet : décalage de collage posé sur les ${decalages.length} sections`
            + ` (${faux.length === 0 ? "toutes justes" : faux.map((f) => f.join(" ")).join(", ")})`,
            decalages.length > 0 && faux.length === 0
        );

        const apresHero = [];

        for (let noeud = heroVolet.nextElementSibling; noeud !== null; noeud = noeud.nextElementSibling) {
            apresHero.push(noeud);
        }

        const transparentes = apresHero.filter(
            (section) => styleOf(section).backgroundColor === "rgba(0, 0, 0, 0)"
        );

        assert(
            `volet : les ${apresHero.length} sections sous le hero ont un fond opaque`
            + ` (${transparentes.length === 0 ? "toutes" : transparentes.map((n) => n.className).join(", ")})`,
            apresHero.length > 0 && transparentes.length === 0
        );
    }

    /* --------------------------------------------------------------------- *
     * Coins hauts des sections empilées.
     *
     * Le rayon seul ne suffit pas : sans le CHEVAUCHEMENT de la même valeur,
     * l'encoche des deux coins laisse voir ce qui peint derrière — le blanc de
     * `.main-content` — soit deux oreilles claires à chaque épaule. Les deux
     * assertions vont donc ensemble, comme le texte blanc et son aplat.
     *
     * Éprouvé sur l'état rendu et non sur la règle : celle-ci n'est sous aucune
     * requête de média, la campagne la voit donc telle qu'un visiteur.
     * --------------------------------------------------------------------- */
    const pageAccueil = doc.querySelector(".front-page");

    if (pageAccueil !== null && win.innerWidth === 1440) {
        const panneaux = [...pageAccueil.children].filter(
            (noeud) => ! noeud.classList.contains("hero")
                && ! noeud.classList.contains("screen-reader-text")
        );
        const rayons = panneaux.map((noeud) => {
            const cs = styleOf(noeud);

            return `${cs.borderTopLeftRadius}/${cs.borderTopRightRadius}/${cs.borderBottomLeftRadius}`;
        });

        assert(
            `sections : coins hauts arrondis à 48, bas francs (${[...new Set(rayons)].join(", ")})`,
            panneaux.length > 0 && rayons.every((r) => r === "48px/48px/0px")
        );

        // Un arrondi ne se lit que contre une AUTRE couleur, et quatre des cinq
        // sections partagent le bleu pâle. L'ombre est ce qui donne une arête
        // au volet dans ce cas : sans elle le rayon existe sans se voir.
        const ombres = panneaux.map((noeud) => styleOf(noeud).boxShadow);

        assert(
            `sections : arête du volet portée par une ombre (${[...new Set(ombres)].join(" | ")})`,
            ombres.every((ombre) => ombre !== "none")
        );

        // Un volet prend toute la hauteur de la vue. Éprouvé sur la RÈGLE :
        // rendu, le plancher ne mord que sur une section plus courte que
        // l'écran, et la campagne ne garantit pas d'en avoir une — à 1440 × 900
        // les technologies font déjà 927. Une assertion sur les hauteurs
        // rendues passerait donc sans la règle.
        //
        // Le sélecteur est celui que Chrome SÉRIALISE, pas celui de la source :
        // le `*` redondant devant `:not()` est retiré. Écrit tel quel dans la
        // feuille, `.front-page > *:not(…)` ne trouve rien.
        assert(
            "sections : un volet ne descend pas sous une hauteur de vue",
            trouverRegle(doc, ".front-page > :not(.hero, .screen-reader-text)", (regle) => (
                regle.style.minHeight === "100svh" ? true : null
            )) === true
        );

        const trop = panneaux.filter((noeud) => noeud.offsetHeight < win.innerHeight - 1);

        assert(
            `sections : les ${panneaux.length} volets couvrent la vue`
            + ` (${trop.length === 0 ? "tous" : trop.map((n) => `${n.className.split(" ")[0]} ${n.offsetHeight}`).join(", ")})`,
            trop.length === 0
        );

        // Le bloc qui suit le hero ARRIVE APRÈS lui : aucun recouvrement.
        // Arbitré par le client — la maquette montre un chevauchement à cet
        // endroit pour dire l'intention de volet, pas pour être reproduit.
        assert(
            `sections : le bloc sous le hero ne chevauche pas (${styleOf(panneaux[0]).marginTop})`,
            styleOf(panneaux[0]).marginTop === "0px"
        );

        // Ce qui peint derrière SES épaules à lui n'est pas un chevauchement
        // mais le débord du visuel du hero, d'exactement un rayon. Sans ce
        // débord, l'encoche laissait voir le blanc de `.main-content` — mesuré
        // à 1440 × 1100, défilement 0 : le point (3, 904) peignait
        // rgb(255, 255, 255). Les deux déclarations sont solidaires.
        const visuel = heroVolet === null ? null : heroVolet.querySelector(".hero__image");

        if (visuel !== null) {
            const debord = Math.round(
                visuel.getBoundingClientRect().bottom - heroVolet.getBoundingClientRect().bottom
            );

            assert(
                `volet : le visuel du hero déborde d'un rayon sous sa boîte (${debord}px)`,
                debord === 48
            );
        }

        // Les suivantes chevauchent : c'est ce qui met la section précédente
        // derrière leurs épaules, à la place du blanc du conteneur.
        const chevauchements = panneaux.slice(1).map((noeud) => styleOf(noeud).marginTop);

        assert(
            `sections : les suivantes chevauchent la précédente de 48 (${[...new Set(chevauchements)].join(", ")})`,
            chevauchements.length > 0 && chevauchements.every((m) => m === "-48px")
        );
    }

    /* --------------------------------------------------------------------- *
     * Un seul panneau ouvert à la fois, PAR GROUPE.
     *
     * Éprouvé sur les deux groupes de la page — l'accordéon des traitements et
     * les cartes de technologie — et sur leur INDÉPENDANCE : ouvrir une carte
     * ne doit pas refermer l'accordéon. C'est la seule chose qu'un groupe posé
     * au mauvais niveau casserait sans rien signaler.
     * --------------------------------------------------------------------- */
    const groupes = [...doc.querySelectorAll("[data-disclosure-group]")];

    if (groupes.length > 0 && win.innerWidth === 1440) {
        const ouverts = (racine) => [...racine.querySelectorAll("[data-disclosure]")]
            .filter((noeud) => noeud.getAttribute("aria-expanded") === "true");

        assert(
            `panneaux : ${groupes.length} groupes déclarés (${groupes.map((n) => n.className.split(" ")[0]).join(", ")})`,
            groupes.length === 2
        );

        // AU CHARGEMENT déjà : le champ « ouvert » est contribuable, deux
        // panneaux cochés ne doivent pas donner deux panneaux ouverts.
        //
        // CETTE ASSERTION NE PEUT PAS ÉCHOUER SUR LE CONTENU SEMÉ, et c'est
        // déclaré plutôt que tu : le contenu de démonstration ne coche qu'un
        // panneau par groupe, donc l'invariant tient même sans le code qui
        // l'applique. Vérifié — l'application au chargement retirée, elle reste
        // VERTE. Elle est gardée comme garde-fou pour un contenu futur, pas
        // comme preuve du dispositif d'aujourd'hui.
        assert(
            `panneaux : au plus un ouvert par groupe au chargement (${groupes.map((g) => ouverts(g).length).join(", ")})`,
            groupes.every((g) => ouverts(g).length <= 1)
        );

        const accordeon = doc.querySelector(".accordion");

        if (accordeon !== null) {
            const boutons = [...accordeon.querySelectorAll("[data-disclosure]")];
            const techno = doc.querySelector(".block-techno");
            const ouvertsTechnoAvant = techno === null ? 0 : ouverts(techno).length;

            boutons[0].click();
            const apresPremier = ouverts(accordeon);

            boutons[1].click();
            const apresSecond = ouverts(accordeon);

            assert(
                `accordéon : ouvrir un panneau referme l'autre (${apresSecond.length} ouvert)`,
                apresPremier.length === 1 && apresSecond.length === 1
                    && apresSecond[0] === boutons[1]
            );
            // Un clic sur un panneau OUVERT le referme : l'exclusivité ne doit
            // pas transformer l'accordéon en sélecteur à choix obligatoire.
            boutons[1].click();
            assert(
                `accordéon : un second clic referme (${ouverts(accordeon).length} ouvert)`,
                ouverts(accordeon).length === 0
            );
            // Et les deux groupes sont INDÉPENDANTS.
            assert(
                `panneaux : les groupes n'interfèrent pas (${techno === null ? "aucun" : ouverts(techno).length} côté technologies, inchangé)`,
                techno === null || ouverts(techno).length === ouvertsTechnoAvant
            );
        }
    }

    /* --------------------------------------------------------------------- *
     * Les rails filent jusqu'aux DEUX bords, et la page est bornée à 1920.
     *
     * Les deux vont ensemble : c'est parce que le rail n'est plus arrêté à
     * gauche qu'il faut une largeur de page maximale, sinon il s'étale sans fin
     * sur un écran très large.
     *
     * Le bornage est éprouvé sur la RÈGLE : la campagne joue à 1440 et 320, le
     * plafond de 1920 n'y est donc jamais atteint et la largeur rendue ne dirait
     * rien. Vérifié à la main en abaissant le plafond à 1200 — en-tête, `main`,
     * pied de page ET le visuel FIXÉ du pied de page se centrent tous dans la
     * largeur, et les côtés montrent le fond de page.
     * --------------------------------------------------------------------- */
    if (win.innerWidth === 1440) {
        // Les trois blocs de premier niveau déclarent LA MÊME largeur maximale.
        //
        // Éprouvé sur leur ÉGALITÉ et non sur une valeur : le bornage est
        // suspendu à `100%` pour l'instant, et une assertion écrite sur 1920
        // aurait rougi sur une suspension voulue. Ce qui ne doit jamais varier,
        // c'est qu'ils soient bornés ENSEMBLE — trois blocs qui divergent
        // décalent la page d'un cran à l'autre.
        const bornes = [".main-content", ".site-header", ".footer-reveal"].map((nom) => [
            nom,
            trouverRegle(doc, nom, (regle) => regle.style.maxWidth || null),
        ]);

        assert(
            `page : les trois blocs partagent la même borne (${bornes.map(([n, v]) => `${n} ${v}`).join(", ")})`,
            bornes.every(([, v]) => v !== null && v === bornes[0][1])
        );

        // Le retrait de la première vignette est un REMBOURRAGE du rail, pas un
        // retrait de la section : c'est ce qui laisse les images sortir par le
        // bord gauche au défilement, comme elles le font à droite.
        const inset = Math.round((win.innerWidth - 1118) / 2);

        for (const [nom, section] of [["histoire", ".block-intro"], ["technologies", ".block-techno"]]) {
            const rail = doc.querySelector(`${section} .carousel__rail`);

            if (rail === null) {
                continue;
            }

            const cadre = rail.getBoundingClientRect();
            const premier = rail.querySelector(".carousel__item");
            const avant = Math.round(premier.getBoundingClientRect().left);

            rail.scrollLeft = 400;
            const apres = Math.round(premier.getBoundingClientRect().left);
            rail.scrollLeft = 0;

            // Plein-bord À DROITE, comme avant, et désormais À GAUCHE aussi.
            // Le rail des cartes inclinées déborde de 12px par compensation du
            // pivot : la tolérance les couvre.
            assert(
                `${nom} : le rail touche les deux bords (${Math.round(cadre.left)} → ${Math.round(cadre.right)} / ${win.innerWidth})`,
                cadre.left <= 0.5 && cadre.left >= -13 && Math.round(cadre.right) === win.innerWidth
            );
            // La boîte englobante d'une carte inclinée déborde de 12 : on
            // compare donc à 12 près, ce qui reste bien plus serré que l'écart
            // que la régression produisait.
            assert(
                `${nom} : la première vignette garde son retrait au repos (${avant} / ${inset})`,
                Math.abs(avant - inset) <= 12
            );
            assert(
                `${nom} : au défilement, elle sort par le bord gauche (${apres})`,
                apres < 0
            );
        }

        const galerie = doc.querySelector(".block-intro [data-carousel]");

        if (galerie !== null) {
            const railGalerie = galerie.querySelector(".carousel__rail");

            assert(
                `histoire : rail de 629 de haut (${Math.round(railGalerie.getBoundingClientRect().height)})`,
                Math.round(railGalerie.getBoundingClientRect().height) === 629
            );
            // Les flèches sont retirées : la galerie avance avec le défilement
            // de la page. L'indicateur, lui, reste — il dit l'avancement.
            assert(
                `histoire : aucune flèche (${galerie.querySelectorAll("[data-carousel-prev], [data-carousel-next]").length})`,
                galerie.querySelectorAll("[data-carousel-prev], [data-carousel-next]").length === 0
            );
            /* -------------------------------------------------------- *
             * La géométrie du rail se mesure ICI et non dans le bloc des
             * contrôles, qui vise désormais les technologies.
             *
             * Ces cotes sont celles de la maquette pour CETTE galerie, et
             * elles ne supportent pas le rail des cartes : une carte inclinée
             * a une boîte englobante plus large que sa boîte de mise en page,
             * ce qui gonflait la somme attendue de 69px, et son débord mange
             * 12 des 36px de respiration finale.
             * -------------------------------------------------------- */
            const cadres = [...railGalerie.querySelectorAll(".carousel__item")];
            const largeurs = cadres.map((node) => node.getBoundingClientRect().width);
            const attendu =
                largeurs.reduce((total, largeur) => total + largeur, 0) +
                (cadres.length - 1) * 12 +
                // Les DEUX rembourrages : celui de gauche porte le retrait de
                // la première vignette depuis que le rail est plein-bord.
                parseFloat(win.getComputedStyle(railGalerie).paddingLeft) +
                parseFloat(win.getComputedStyle(railGalerie).paddingRight);

            assert(
                `histoire : course = somme des visuels + respiration (${railGalerie.scrollWidth} / ${Math.round(attendu)})`,
                Math.abs(railGalerie.scrollWidth - attendu) < 2
            );
            // Un cadre étroit rempli d'une photo se lit comme un visuel
            // tronqué : le reliquat de 36px est une respiration, pas une image.
            assert(
                `histoire : aucun visuel étroit (${largeurs.map((l) => Math.round(l)).join("/")})`,
                largeurs.every((largeur) => largeur >= 100)
            );

            railGalerie.scrollTo({ left: railGalerie.scrollWidth, behavior: "auto" });

            const finVisuel = cadres[cadres.length - 1].getBoundingClientRect();
            const bordRail = railGalerie.getBoundingClientRect().right;

            assert(
                `histoire : dernier visuel entier en fin de course (respiration ${Math.round(bordRail - finVisuel.right)}px)`,
                finVisuel.right <= bordRail + 1 && Math.round(bordRail - finVisuel.right) === 36
            );

            railGalerie.scrollTo({ left: 0, behavior: "auto" });

            assert(
                "histoire : l'indicateur d'avancement reste",
                galerie.querySelector("[data-carousel-thumb]") !== null
            );
            // ET PAS DE GLISSEMENT : les flèches en étaient l'alternative, et
            // le WCAG 2.5.7 en exige une. Un geste sans alternative ne doit pas
            // exister — la classe qui l'annonce ne doit donc pas être posée.
            assert(
                `histoire : pas de glisser-déposer sans alternative (${galerie.className})`,
                ! galerie.classList.contains("carousel--draggable")
            );
        }
    }

    /* --------------------------------------------------------------------- *
     * Le survol suit partout le principe du bouton secondaire.
     *
     * Trois familles portent la même construction — contournées au repos,
     * remplies du VOILE au survol, bordure effacée : le bouton d'action
     * secondaire, les flèches de carrousel et l'icône d'accordéon. Éprouvées
     * ensemble : c'est la règle qui ne doit pas souffrir d'exception, et une
     * exception ajoutée plus tard doit faire rougir la campagne.
     *
     * Lu sur la RÈGLE, la campagne n'ayant pas de pointeur.
     * --------------------------------------------------------------------- */
    if (win.innerWidth === 1440) {
        const survols = [
            [".cta--outline:hover .cta__label", "bouton secondaire"],
            [".carousel__button:hover", "flèche de carrousel"],
            [".accordion__trigger:hover", "icône d'accordéon"],
        ];

        for (const [selecteur, nom] of survols) {
            assert(
                `survol : ${nom} — le voile remplit, la bordure s'efface`,
                trouverRegle(doc, selecteur, (regle) => (
                    regle.style.backgroundColor === "rgba(0, 56, 122, 0.3)"
                        && regle.style.borderColor === "rgba(0, 0, 0, 0)"
                        ? true
                        : null
                )) === true
            );
        }
    }

    /* --------------------------------------------------------------------- *
     * Les liens textuels du pied de page.
     *
     * Au survol : PAS de soulignement, l'épaisseur passe à 600. Les deux vont
     * ensemble — remettre un soulignement en gardant l'épaisseur donnerait
     * deux signaux là où le client n'en veut qu'un.
     *
     * L'état de survol est lu sur la règle, la campagne n'ayant pas de
     * pointeur ; la famille, elle, se mesure sur l'état rendu.
     * --------------------------------------------------------------------- */
    const lienPied = doc.querySelector(".site-footer__list a");

    if (lienPied !== null && win.innerWidth === 1440) {
        assert(
            "pied de page : le survol épaissit et ne souligne pas",
            trouverRegle(doc, ".site-footer__list a:hover", (regle) => (
                regle.style.fontWeight === "600"
                    && regle.style.textDecorationLine === ""
                    ? true
                    : null
            )) === true
        );
        assert(
            `pied de page : pas de soulignement au repos (${styleOf(lienPied).textDecorationLine})`,
            styleOf(lienPied).textDecorationLine === "none"
        );
        // La famille est HÉRITÉE du `body` : l'éprouver ici vérifie que rien
        // ne la redéclare en chemin, ce qui créerait une seconde source.
        assert(
            `pied de page : la pile Inter est bien héritée (${styleOf(lienPied).fontFamily.split(",")[0]})`,
            styleOf(lienPied).fontFamily.startsWith("Inter")
                && styleOf(lienPied).fontFamily === styleOf(doc.body).fontFamily
        );
    }

    /* --------------------------------------------------------------------- *
     * Les colonnes d'étiquette restent au même niveau.
     *
     * `align-self: start` est éprouvé AUTANT que `position: sticky` : sans lui,
     * l'élément de grille s'étire à sa rangée, n'a plus de course, et le
     * collage devient SILENCIEUSEMENT inopérant. C'est le seul des deux qu'un
     * relecteur pourrait retirer en croyant nettoyer.
     *
     * Et le calage lit `--header-height` : un nombre écrit à la main serait
     * faux dès que l'en-tête revient à la ligne.
     * --------------------------------------------------------------------- */
    const collantes = [
        [".block-treatments__label", "traitements"],
        [".block-info__aside", "informations pratiques"],
    ];

    for (const [selecteur, nom] of collantes) {
        const colonne = doc.querySelector(selecteur);

        if (colonne === null || win.innerWidth !== 1440) {
            continue;
        }

        const cs = styleOf(colonne);

        assert(
            `${nom} : la colonne est collante et a sa course (${cs.position}, align-self ${cs.alignSelf})`,
            cs.position === "sticky" && cs.alignSelf === "start"
        );
        assert(
            `${nom} : le calage suit la hauteur d'en-tête publiée`,
            trouverRegle(doc, selecteur, (regle) => (
                regle.style.top.includes("--header-height") ? true : null
            )) === true
        );
    }

    /* --------------------------------------------------------------------- *
     * Le numéro d'étape porte le style H3 de la maquette.
     *
     * Deux valeurs de Figma ne se recopient pas, et ce sont celles-là qu'on
     * éprouve : le `font-weight: 90` est le nom de la coupe et doit devenir
     * 400, et le `line-height: 120 %` doit rendre 29 — l'entier auquel Figma
     * arrondit — et non 28,8.
     * --------------------------------------------------------------------- */
    const numeroEtape = doc.querySelector(".journey__number");

    if (numeroEtape !== null && win.innerWidth === 1440) {
        const cs = styleOf(numeroEtape);
        const titreEtape = doc.querySelector(".journey__title");
        const ct = styleOf(titreEtape);
        const centre = (node, style, avecPadding) => Math.round(
            node.getBoundingClientRect().top
            + (avecPadding ? parseFloat(style.paddingTop) : 0)
            + parseFloat(style.lineHeight) / 2,
        );

        assert(
            `parcours : numéro en Sligoil 24 (${cs.fontFamily.split(",")[0]} ${cs.fontSize})`,
            cs.fontFamily.startsWith("Sligoil") && cs.fontSize === "24px"
        );
        assert(
            `parcours : numéro turquoise (${cs.color})`,
            cs.color === "rgb(4, 139, 140)"
        );
        assert(
            `parcours : le poids 90 de Figma vaut 400 en CSS (${cs.fontWeight})`,
            cs.fontWeight === "400"
        );
        assert(
            `parcours : interligne rendu à l'entier, 29 et non 28,8 (${cs.lineHeight})`,
            cs.lineHeight === "29px"
        );
        assert(
            `parcours : aucun interlettrage (${cs.letterSpacing})`,
            cs.letterSpacing === "normal"
        );
        // Le calage vertical est la seule chose que le changement de taille
        // pouvait casser en silence : la formule compare deux boîtes de ligne.
        assert(
            `parcours : numéro centré sur la première ligne du titre (${centre(numeroEtape, cs, true)} / ${centre(titreEtape, ct, false)})`,
            Math.abs(centre(numeroEtape, cs, true) - centre(titreEtape, ct, false)) <= 1
        );
    }

    // Le numéro NE RÉTRÉCIT PAS avec la vue, et c'est le contraste qui
    // l'impose : le turquoise mesure 3,87:1 sur le panneau, conforme au seuil
    // de 3 du texte large — 24px — mais pas au 4,5 du texte courant. Éprouvé à
    // TOUTES les largeurs de la campagne, puisque c'est en rétrécissant qu'il
    // basculerait du bon côté au mauvais.
    const numeroToutesVues = doc.querySelector(".journey__number");

    if (numeroToutesVues !== null) {
        assert(
            `parcours : le numéro reste à 24 quelle que soit la vue (${styleOf(numeroToutesVues).fontSize} à ${win.innerWidth}px)`,
            styleOf(numeroToutesVues).fontSize === "24px"
        );
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

        // La section ne mesure plus un écran PAR ÉTAPE : un écran, plus un
        // budget de défilement par transition. C'est ce qui rend le rail
        // réactif à la molette. Le mesurer, et non le lire, couvre l'addition.
        const budget = 0.8;
        const attenduHaut = win.innerHeight * (1 + 5 * budget);
        assert(
            `section : ${(1 + 5 * budget).toFixed(2)} écrans — un pour la vue collée, le reste pour les cinq transitions (${round(journey.offsetHeight)} / ${round(attenduHaut)})`,
            Math.abs(journey.offsetHeight - attenduHaut) < 4
        );

        // La translation est désormais ANIMÉE : sans cette neutralisation, les
        // trois mesures ci-dessous liraient le rail en cours de route.
        const railParcours = doc.querySelector(".journey__track");
        const remplissageParcours = doc.querySelector(".journey__progress-fill");

        assert(
            `translation animée (${win.getComputedStyle(railParcours).transitionProperty})`,
            win.getComputedStyle(railParcours).transitionProperty.includes("transform")
                && parseFloat(win.getComputedStyle(railParcours).transitionDuration) > 0
        );

        railParcours.style.transition = "none";
        remplissageParcours.style.transition = "none";

        // Les fractions choisies PROUVENT l'arrondi : sans lui, 0,31 donnerait
        // 1,55 vue et un remplissage de 283, et non 2 vues et 333.
        //
        // 0,9 est le cas de la DERNIÈRE carte : elle doit être entièrement en
        // place alors qu'il reste un dixième de la course à parcourir, sinon
        // elle n'apparaît qu'au moment où la section se décolle. Sans arrondi,
        // 0,9 laisserait le rail à 4,5 vues, c'est-à-dire à cheval.
        for (const [progres, remplissage, vues] of [[0, 111, 0], [0.31, 333, 2], [0.62, 444, 3], [0.9, 666, 5], [1, 666, 5]]) {
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

        // Le repli existe : un moteur sans `round()` doit garder une
        // translation, même continue. Éprouvé sur la règle, la garde
        // `@supports` étant vraie ici.
        assert(
            "translation de repli déclarée hors de la garde",
            trouverRegle(doc, ".journey__track", (regle) => {
                const trace = regle.style.transform;

                // `null` et non `false` pour toute règle qui ne dit rien : le
                // premier `.journey__track` de la feuille ne porte AUCUNE
                // translation, et un `false` y aurait arrêté la recherche avant
                // d'atteindre celle qu'on cherche.
                return trace !== "" && !trace.includes("round(")
                    && trace.includes("var(--journey-progress") ? true : null;
            }) === true
        );

        /* ----------------------------------------------------------------- *
         * Une étape par cran de molette, et rien pendant la cadence.
         *
         * Éprouvable sans image d'animation : le gestionnaire lit la position
         * de la section et la corrige dans le même appel, sans passer par
         * `requestAnimationFrame`. Les évènements sont synthétisés, c'est le
         * seul moyen d'éprouver un geste sans périphérique.
         * ----------------------------------------------------------------- */
        const haut = journey.getBoundingClientRect().top + win.scrollY;
        const course = journey.offsetHeight - win.innerHeight;
        const etape = course / 5;
        const cran = (delta) => {
            const evenement = new win.WheelEvent("wheel", {
                deltaY: delta,
                cancelable: true,
                bubbles: true,
            });
            win.dispatchEvent(evenement);

            return evenement.defaultPrevented;
        };

        const pause = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
        const traine = async (tirs, ecart) => {
            for (let tir = 0; tir < tirs; tir += 1) {
                await pause(ecart);
                cran(100);
            }
        };

        // Hors de la section, le geste appartient à la page : sans cette borne
        // le site entier deviendrait inutilisable à la molette. Ce cran désarme
        // aussi l'état « collée », comme le ferait un vrai défilement d'approche.
        win.scrollTo(0, Math.round(haut) - 40);
        assert("hors section : le cran n'est pas confisqué", cran(100) === false);

        // ARRIVÉE. Deux choses se cumulaient. Le geste d'entrée n'était pas
        // confisqué — la section n'était pas encore collée — et Chrome ANIME le
        // défilement de molette : la page continue de glisser après le dernier
        // évènement, sans qu'aucun ne soit là pour l'arrêter.
        //
        // Le `scrollTo` ci-dessous rejoue cette glissade : 0,6 étape au-delà du
        // bord, ce qu'un évènement synthétisé ne peut pas produire lui-même
        // puisqu'il ne déclenche pas l'action par défaut du navigateur. C'est ce
        // dépassement qu'il ne faut PAS entériner — s'ancrer « au plus proche »
        // aurait ici retenu la deuxième carte.
        win.scrollTo(0, Math.round(haut + 0.6 * etape));
        const arrivee = cran(100);

        assert("arrivée : le geste d'entrée est confisqué", arrivee === true);
        assert(
            `arrivée : aligné sur la première étape, sans avancer (${round(win.scrollY - haut)}px)`,
            Math.abs(win.scrollY - haut) < 2
        );

        await traine(10, 100);
        assert(
            `arrivée : la traîne d'entrée n'emporte pas la première carte (${round(win.scrollY - haut)}px)`,
            Math.abs(win.scrollY - haut) < 2
        );

        // Même chose EN REMONTANT : on arrive alors par le bas, et c'est la
        // DERNIÈRE étape qu'il faut bloquer.
        win.scrollTo(0, Math.round(haut + course) + 40);
        assert("hors section par le bas : le cran n'est pas confisqué", cran(-100) === false);

        win.scrollTo(0, Math.round(haut + course - 0.6 * etape));
        const arriveeBas = cran(-100);

        assert("arrivée par le bas : le geste d'entrée est confisqué", arriveeBas === true);
        assert(
            `arrivée par le bas : aligné sur la dernière étape (${round(win.scrollY - haut - course)}px)`,
            Math.abs(win.scrollY - haut - course) < 2
        );

        await traine(10, 100);
        assert(
            `arrivée par le bas : la traîne n'emporte pas la dernière carte (${round(win.scrollY - haut - course)}px)`,
            Math.abs(win.scrollY - haut - course) < 2
        );

        // Amené AU MILIEU de la section autrement qu'à la molette — clavier,
        // barre de défilement — le premier cran ne doit PAS être pris pour une
        // arrivée : le visiteur serait ramené en arrière de plusieurs écrans.
        win.scrollTo(0, 0);
        cran(100);
        win.scrollTo(0, Math.round(haut + 2 * etape));
        await pause(1800);
        cran(100);
        assert(
            `au milieu : le premier cran avance, il ne ramène pas au début (${round((win.scrollY - haut) / etape)} étape(s))`,
            Math.abs(win.scrollY - haut - 3 * etape) < 2
        );

        // Retour au premier palier pour la suite.
        win.scrollTo(0, 0);
        cran(100);
        win.scrollTo(0, Math.round(haut) + 20);
        cran(100);
        await pause(1800);

        // Le geste SUIVANT, lui, porte d'exactement une étape.
        await pause(1800);
        const confisque = cran(100);
        const apresUnCran = win.scrollY;

        assert("section collée : le cran est confisqué", confisque === true);
        assert(
            `un cran porte d'exactement une étape (${round(apresUnCran - haut)} / ${round(etape)})`,
            Math.abs(apresUnCran - haut - etape) < 2
        );

        // Trois crans de plus AVANT la fin de la cadence : absorbés sans effet.
        // C'est ce qui rend le geste presque idempotent — l'amplitude d'une
        // roulette lâchée d'un coup ne compte pas.
        const absorbes = [cran(100), cran(100), cran(100)];

        assert(`crans rapprochés confisqués (${absorbes.join(", ")})`,
            absorbes.every((prevenu) => prevenu === true));
        assert(
            `page immobile pendant la cadence (${round(win.scrollY)} = ${round(apresUnCran)})`,
            Math.abs(win.scrollY - apresUnCran) < 2
        );

        // La TRAÎNE d'un geste. Un pavé tactile émet près d'une seconde après
        // que le doigt a quitté la surface : avec une cadence fixe, cette
        // inertie déclenchait une seconde étape — deux cartes passaient d'un
        // seul geste.
        await pause(1800);
        cran(100);
        const departTraine = win.scrollY;

        await traine(10, 100);
        assert(
            `une traîne d'une seconde ne vaut qu'une étape (${round(win.scrollY - departTraine)}px de plus)`,
            Math.abs(win.scrollY - departTraine) < 2
        );

        // Et le plafond : un défilement CONTINU doit finir par avancer, sinon
        // la section devient un cul-de-sac.
        const departContinu = win.scrollY;

        await traine(40, 50);
        assert(
            `un défilement continu franchit le plafond (${round(win.scrollY - departContinu)}px de plus)`,
            win.scrollY - departContinu >= etape - 2
        );

        /* ----------------------------------------------------------------- *
         * L'ARRÊT sur les cartes de bout.
         *
         * La carte est posée par une bascule — et non par un `scrollTo`, sinon
         * l'arrêt ne serait pas armé et l'assertion ne prouverait rien. Le geste
         * qui vient de la poser ne doit pas l'emporter par sa traîne ; il faut
         * un second geste pour sortir.
         * ----------------------------------------------------------------- */
        win.scrollTo(0, 0);
        cran(100);
        win.scrollTo(0, Math.round(haut + 4 * etape));
        await pause(1800);
        cran(100);

        assert(
            `dernière étape posée par une bascule (${round((win.scrollY - haut) / etape)} étape(s))`,
            Math.abs(win.scrollY - haut - course) < 2
        );

        const surLaDerniere = win.scrollY;

        assert("dernière étape : la traîne ne fait pas sortir", cran(100) === true);
        await traine(10, 100);
        assert(
            `dernière étape : arrêt marqué à l'écran (${round(win.scrollY - surLaDerniere)}px)`,
            Math.abs(win.scrollY - surLaDerniere) < 2
        );

        await pause(1800);
        assert("dernière étape : un second geste rend la main", cran(100) === false);

        // Symétrique en haut : la première carte marque le même arrêt avant de
        // laisser remonter dans la section précédente.
        win.scrollTo(0, 0);
        cran(100);
        win.scrollTo(0, Math.round(haut + etape));
        await pause(1800);
        cran(-100);

        assert(
            `première étape posée par une bascule (${round((win.scrollY - haut) / etape)} étape(s))`,
            Math.abs(win.scrollY - haut) < 2
        );
        assert("première étape : la traîne ne fait pas remonter", cran(-100) === true);

        await pause(1800);
        assert("première étape : un second geste rend la main", cran(-100) === false);

        win.scrollTo(0, 0);

        railParcours.style.removeProperty("transition");
        remplissageParcours.style.removeProperty("transition");
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
        // Le BORD DU CONTENU, et non celui de la boîte : le rail est tiré de
        // 12px vers la gauche pour que le coin de la première carte inclinée
        // reste visible, et il les reprend en retrait. C'est la carte qui doit
        // tomber à 161, comme la maquette l'exige.
        const railTechno = doc.querySelector(".carousel--cards .carousel__rail");
        const retraitRail = railTechno === null
            ? 0
            : parseFloat(styleOf(railTechno).paddingLeft);

        cote("techno : rail, bord gauche du contenu",
            railCards === null ? null : railCards.left + retraitRail, 161);

        // Une carte inclinée déborde de sa boîte : le rail rognait ce débord
        // sur la première, seule à ne pas avoir de voisine pour le porter.
        const premiereCarte = doc.querySelector(".carousel--cards .carousel__item");

        if (premiereCarte !== null && railCards !== null) {
            const debord = premiereCarte.getBoundingClientRect().left - railCards.left;

            assert(
                `techno : la 1re carte inclinée n'est pas rognée (${debord.toFixed(1)}px de marge)`,
                debord >= -0.5
            );
        }
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

        // Rien ne doit être peint SOUS le visuel d'une carte. Le découpage
        // arrondi d'une carte inclinée est anticrénelé : son pixel de bord
        // mélange le visuel avec ce qui est dessous, et l'aplat bleu foncé y
        // dessinait un liseré d'un pixel. Mesuré sur la construction réelle :
        // le bord valait #9CAEBA quand le mélange visuel/fond attendu était
        // chaud, et l'écart médian au mélange légitime est passé de 18,8 à 2,9.
        //
        // L'aplat n'est pas supprimé pour autant : il ne sert plus qu'aux
        // cartes sans visuel, et sa règle est éprouvée juste après.
        const cartesVisuel = [...doc.querySelectorAll(".tech-card")]
            .filter((node) => node.querySelector(".tech-card__image") !== null);
        const fonds = cartesVisuel.map((node) => styleOf(node).backgroundColor);
        assert(
            `techno : aucun aplat sous un visuel (${cartesVisuel.length} cartes : ${[...new Set(fonds)].join(", ")})`,
            cartesVisuel.length > 0
                && fonds.every((fond) => fond === "rgba(0, 0, 0, 0)" || fond === "transparent")
        );
        assert(
            "techno : l'aplat de repli subsiste pour une carte sans visuel",
            trouverRegle(doc, ".tech-card--plain", (regle) => (
                regle.style.backgroundColor === "rgb(0, 56, 122)" ? true : null
            )) === true
        );

        cote("infos : étiquette, bord gauche", boite(".block-info .tag")?.left ?? null, 161);
        cote("infos : visuel, largeur", boite(".block-info__media")?.width ?? null, 440);
        cote("infos : visuel, hauteur", boite(".block-info__media")?.height ?? null, 549);
        cote("infos : colonne de droite, bord gauche", boite(".block-info__entry")?.left ?? null, 726);
        cote("infos : colonne de droite, bord droit", boite(".block-info__entry")?.right ?? null, 1279);
        cote("infos : icône, largeur", boite(".block-info__icon")?.width ?? null, 24);
        cote("infos : texte, bord gauche", boite(".block-info__head")?.left ?? null, 774);
        cote("infos : bouton contourné, bord droit", boite(".block-info .cta--outline")?.right ?? null, 1279);

        /* ------------------------------------------------------------------ *
         * Les deux variantes de bouton d'action, éprouvées SÉPARÉMENT.
         *
         * Elles ne partagent plus leur rendu : la primaire porte du texte blanc
         * sur un aplat bleu, la secondaire du texte bleu sur fond transparent et
         * une bordure. Une assertion commune passerait sur l'une en masquant
         * l'autre — c'est ce qui est arrivé quand les deux étaient bleues.
         * ------------------------------------------------------------------ */
        const primaires = [...doc.querySelectorAll(".cta--solid .cta__label")];
        const textesPrimaires = primaires.map((node) => styleOf(node).color);
        const aplatsPrimaires = primaires.map((node) => styleOf(node).backgroundColor);

        assert(
            `bouton primaire : texte blanc sur les ${primaires.length} pastilles (${[...new Set(textesPrimaires)].join(", ")})`,
            primaires.length > 0 && textesPrimaires.every((teinte) => teinte === "rgb(255, 255, 255)")
        );
        // Le blanc n'est lisible que sur un aplat foncé : les deux vont
        // ensemble, et mesurer la seule couleur du texte laisserait passer du
        // blanc sur blanc.
        //
        // Cette mesure est aussi la GARDE de la silhouette : le tracé ne
        // dispense pas les pastilles de leur propre aplat. Vidées au profit du
        // seul `<path>`, elles ont fait tomber les six libellés à 1,07:1 —
        // aucun contrôleur de contraste ne voit un SVG.
        assert(
            `bouton primaire : aplat bleu sous ce blanc (${[...new Set(aplatsPrimaires)].join(", ")})`,
            aplatsPrimaires.every((aplat) => aplat === "rgb(0, 56, 122)")
        );

        const secondaires = [...doc.querySelectorAll(".cta--outline .cta__label")];
        const textesSecondaires = secondaires.map((node) => styleOf(node).color);
        const fondsSecondaires = secondaires.map((node) => styleOf(node).backgroundColor);
        const bordsSecondaires = secondaires.map((node) => (
            `${styleOf(node).borderTopWidth} ${styleOf(node).borderTopColor}`
        ));

        assert(
            `bouton secondaire : texte bleu sur les ${secondaires.length} pastilles (${[...new Set(textesSecondaires)].join(", ")})`,
            secondaires.length > 0 && textesSecondaires.every((teinte) => teinte === "rgb(0, 56, 122)")
        );
        // Transparent, et non « la teinte du panneau » : un aplat en dur serait
        // faux partout ailleurs. Le « voir le plan » ne vit pas sur le panneau.
        assert(
            `bouton secondaire : fond transparent (${[...new Set(fondsSecondaires)].join(", ")})`,
            fondsSecondaires.every((fond) => fond === "rgba(0, 0, 0, 0)")
        );
        assert(
            `bouton secondaire : bordure de 1px au voile bleu (${[...new Set(bordsSecondaires)].join(", ")})`,
            bordsSecondaires.every((bord) => bord === "1px rgba(0, 56, 122, 0.3)")
        );

        // Le SURVOL de la variante secondaire, lu sur la RÈGLE : la campagne
        // n'a pas de pointeur, l'état ne se mesure donc pas. Le remplissage y
        // vaut le même voile que la bordure au repos — c'est le CSS Figma — et
        // la bordure s'efface : deux voiles à 30 % superposés composeraient un
        // anneau foncé de 51 unités d'écart avec l'intérieur.
        assert(
            "bouton secondaire : le survol remplit du voile et efface la bordure",
            trouverRegle(doc, ".cta--outline:hover .cta__label", (regle) => (
                regle.style.backgroundColor === "rgba(0, 56, 122, 0.3)"
                    && regle.style.borderColor === "rgba(0, 0, 0, 0)"
                    ? true
                    : null
            )) === true
        );

        /* ------------------------------------------------------------------ *
         * La silhouette des boutons primaires.
         *
         * Même tracé que la barre de navigation, par le même code. Éprouvée sur
         * le `d` du path et non sur une capture : ce qu'un copier-coller de
         * `initPillShape` casse, c'est le rattachement au conteneur — un tracé
         * calé sur le mauvais cadre reste un tracé.
         * ------------------------------------------------------------------ */
        const silhouettes = [...doc.querySelectorAll(".cta--solid")];
        const traces = silhouettes.map((cta) => {
            const path = cta.querySelector(".cta__shape path");

            return path === null ? "" : path.getAttribute("d");
        });

        assert(
            `boutons primaires : ${silhouettes.length} silhouettes tracées (${traces.filter((d) => d !== "" && d !== null).length})`,
            silhouettes.length > 0 && traces.every((d) => typeof d === "string" && d.startsWith("M") && d.endsWith("Z"))
        );
        assert(
            `boutons primaires : classe posée sur chacun (${silhouettes.filter((cta) => cta.classList.contains("cta--shaped")).length} sur ${silhouettes.length})`,
            silhouettes.every((cta) => cta.classList.contains("cta--shaped"))
        );
        // Le tracé part du BORD GAUCHE du bouton, à un rayon près : c'est ce qui
        // dit qu'il est calé sur le bon conteneur. Mesuré sur le premier `M`.
        const departs = traces.map((d) => parseFloat(String(d).slice(1).split(" ")[0]));

        assert(
            `boutons primaires : tracé calé sur le conteneur (départs ${departs.map((x) => x.toFixed(1)).join(", ")})`,
            departs.every((x) => x > 0 && x < 12)
        );

        /* ------------------------------------------------------------------ *
         * Le collet SUIT l'écart, il n'est pas dessiné une fois pour toutes.
         *
         * C'est tout l'intérêt du tracé : sans ce contrôle, une silhouette
         * figée au chargement passerait les trois assertions ci-dessus et ne
         * s'ouvrirait jamais au survol.
         *
         * L'écart est forcé par un style en ligne, puis `resize` est synthétisé
         * — c'est l'autre signal branché sur la même mise à jour. Le survol lui
         * même n'est PAS reproductible ici : `:hover` ne se synthétise pas, et
         * la campagne force `prefers-reduced-motion`, où la transition de marge
         * est neutralisée et n'émet donc aucun `transitionstart`. Cette branche
         * du script reste hors de portée de la recette, et c'est déclaré.
         * ------------------------------------------------------------------ */
        const premier = doc.querySelector(".cta--solid");

        if (premier !== null) {
            const traceCta = premier.querySelector(".cta__shape path");
            const libelle = premier.querySelector(".cta__label");
            // Distance entre les deux poignées de Bézier du collet : elle croît
            // avec l'écart, là où la largeur du tracé entier croît aussi quand
            // c'est seulement le libellé qui s'allonge.
            const poignees = (d) => {
                const courbe = String(d).match(/C([\d.]+) [\d.]+, ([\d.]+) /);

                return courbe === null ? null : parseFloat(courbe[2]) - parseFloat(courbe[1]);
            };
            const auRepos = poignees(traceCta.getAttribute("d"));

            libelle.style.marginLeft = "20px";
            premier.ownerDocument.defaultView.dispatchEvent(new win.Event("resize"));

            const ecarte = poignees(traceCta.getAttribute("d"));

            libelle.style.marginLeft = "";
            premier.ownerDocument.defaultView.dispatchEvent(new win.Event("resize"));

            const revenu = poignees(traceCta.getAttribute("d"));

            assert(
                `collet : s'ouvre avec l'écart (${auRepos?.toFixed(2)} à 2px, ${ecarte?.toFixed(2)} à 20px)`,
                auRepos !== null && ecarte !== null && ecarte > auRepos + 10
            );
            // Et il se referme : une mise à jour qui ne saurait que grandir
            // laisserait le bouton ouvert après la sortie du curseur.
            assert(
                `collet : se referme (${revenu?.toFixed(2)} revenu sur ${auRepos?.toFixed(2)})`,
                revenu !== null && auRepos !== null && Math.abs(revenu - auRepos) < 0.5
            );
        }

        // ET AUCUNE règle `:visited` dans la feuille. Celle qui neutralisait la
        // couleur des liens visités portait une spécificité (0,1,1) : elle
        // l'emportait sur toute classe de composant (0,1,0), et le texte blanc
        // de ces pastilles redevenait bleu dès que le lien avait été visité.
        //
        // Cette assertion porte sur la RÈGLE parce que l'état, lui, est
        // inobservable : `getComputedStyle` ment délibérément sur `:visited`
        // — c'est une protection de la vie privée — et un profil de navigateur
        // neuf n'a de toute façon aucun historique. Les deux mesures faites
        // ici disaient donc « blanc » alors que le défaut était bien réel.
        const visites = [];

        for (const feuille of Array.from(doc.styleSheets)) {
            let regles;

            try {
                regles = feuille.cssRules;
            } catch (erreur) {
                continue;
            }

            const fouiller = (liste) => {
                for (const regle of Array.from(liste || [])) {
                    if (typeof regle.selectorText === "string" && regle.selectorText.includes(":visited")) {
                        visites.push(regle.selectorText);
                    }

                    if (regle.cssRules !== undefined) {
                        fouiller(regle.cssRules);
                    }
                }
            };

            fouiller(regles);
        }

        assert(
            `aucune règle :visited dans la feuille (${visites.length === 0 ? "aucune" : visites.join(", ")})`,
            visites.length === 0
        );

        /* ------------------------------------------------------------------ *
         * Les glyphes des informations pratiques.
         *
         * Les tracés viennent du client et ne sont pas éprouvés : ce sont ses
         * dessins, pas des cotes de maquette à retrouver. Ce qui EST éprouvé,
         * c'est leur intégration — la seule chose qu'un copier-coller d'export
         * casse silencieusement.
         * ------------------------------------------------------------------ */
        const glyphes = [...doc.querySelectorAll(".block-info__icon")];

        if (glyphes.length > 0) {
            const debords = glyphes.map((boite) => {
                const svg = boite.querySelector("svg");

                return svg === null
                    ? 999
                    : svg.getBoundingClientRect().width - boite.getBoundingClientRect().width;
            });

            // Les exports du client mesurent 26 : rendus tels quels, ils
            // débordent de la colonne d'icône, que la maquette mesure à 24.
            assert(
                `glyphes : aucun ne déborde de sa colonne (${debords.map((d) => d.toFixed(0)).join(", ")})`,
                debords.every((debord) => debord <= 0.5)
            );
            // Le trait suit la couleur du texte, donc le jeton : un
            // `stroke="#048B8C"` en dur figerait une teinte que seule la
            // bibliothèque Figma porte, et le glyphe ne se recolorerait plus.
            const teintes = glyphes.map((boite) => styleOf(boite).color);

            assert(
                `glyphes : peints à la teinte de la colonne (${[...new Set(teintes)].join(", ")})`,
                teintes.every((teinte) => teinte === "rgb(4, 139, 140)")
            );
            assert(
                `glyphes : trait en currentColor (${glyphes.length} glyphes)`,
                glyphes.every((boite) => [...boite.querySelectorAll("[stroke]")]
                    .every((trait) => trait.getAttribute("stroke") === "currentColor"))
            );
        }

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
     * La campagne tourne avec `prefers-reduced-motion` forcé : le visuel y est
     * donc `absolute` et défile avec la page, ce qu'on éprouve tel quel. Le mode
     * FIXÉ est éprouvé en posant la déclaration à la main — deux défilements de
     * 200px, et la boîte du visuel ne doit pas bouger d'un pixel.
     * --------------------------------------------------------------------- */
    const revele = doc.querySelector(".footer-reveal");
    const media = revele === null ? null : revele.querySelector(".footer-reveal__media");

    if (media !== null) {
        const round = (valeur) => Math.round(valeur);
        const footer = doc.querySelector(".site-footer");
        // La hauteur DÉCOUVERTE est la réserve du bloc, pas la hauteur du
        // visuel : celui-ci est volontairement plus haut, puisqu'il remonte
        // sous les coins arrondis du panneau.
        const hauteur = parseFloat(styleOf(revele).paddingBottom);
        const principal = doc.querySelector(".main-content");

        /* ------------------------------------------------------------------ *
         * Les deux conditions de peinture. L'effet n'existe que par elles, et
         * aucune ne se voit dans une capture : elles se lisent sur les fonds.
         * ------------------------------------------------------------------ */
        assert(
            `la réserve ne peint aucun fond (${styleOf(revele).backgroundColor})`,
            styleOf(revele).backgroundColor === "rgba(0, 0, 0, 0)"
        );
        assert(
            `le contenu porte l'aplat qui masque le visuel fixé (${principal === null ? "absent" : styleOf(principal).backgroundColor})`,
            principal !== null && styleOf(principal).backgroundColor === "rgb(255, 255, 255)"
        );

        /* ------------------------------------------------------------------ *
         * Le visuel NE BOUGE PAS.
         *
         * La campagne force `prefers-reduced-motion`, où le visuel redevient
         * `absolute` et défile avec la page — un fond qui ne suit pas le contenu
         * est un effet de parallaxe, et c'est ce que la préférence demande
         * d'éviter. Les deux branches sont donc éprouvées : le repli tel qu'il
         * est rendu, et le mode fixé en posant la déclaration à la main.
         * ------------------------------------------------------------------ */
        const hautDe = () => media.getBoundingClientRect().top;

        win.scrollTo(0, 0);
        const replisHaut = hautDe();
        win.scrollTo(0, 200);
        const replisBas = hautDe();

        assert(
            `mouvement réduit : le visuel défile avec la page (${round(replisHaut - replisBas)}px pour 200)`,
            Math.abs(replisHaut - replisBas - 200) < 2
        );

        assert(
            "le visuel est fixé hors du mouvement réduit",
            trouverRegle(doc, ".footer-reveal__media", (regle) => (
                regle.style.position === "fixed" ? true : null
            )) === true
        );

        media.style.position = "fixed";
        win.scrollTo(0, 0);
        const fixeHaut = hautDe();
        win.scrollTo(0, 200);
        const fixeBas = hautDe();
        media.style.removeProperty("position");
        win.scrollTo(0, 0);

        assert(
            `fixé : 200px de défilement ne le déplacent pas (${round(fixeHaut - fixeBas)}px)`,
            Math.abs(fixeHaut - fixeBas) < 1
        );

        assert(
            `le visuel est découvert sur la réserve (${round(media.getBoundingClientRect().bottom - footer.getBoundingClientRect().bottom)}px pour ${round(hauteur)})`,
            media.getBoundingClientRect().bottom - footer.getBoundingClientRect().bottom > hauteur - 4
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
            const rayon = rayonImage(styleOf(footer, "::before"));
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
        const rayon = parseFloat(styleOf(footer, "::before").borderBottomLeftRadius);
        const remonte = footer.getBoundingClientRect().bottom - media.getBoundingClientRect().top;
        assert(
            `le visuel remonte d'un rayon sous le panneau (${remonte.toFixed(0)} pour un rayon de ${rayon})`,
            Math.abs(remonte - rayon) < 2
        );
        assert(
            `le visuel couvre toute la largeur (${media.getBoundingClientRect().width.toFixed(0)} = ${revele.getBoundingClientRect().width.toFixed(0)})`,
            Math.abs(media.getBoundingClientRect().width - revele.getBoundingClientRect().width) < 1
        );
    }

    if (win.innerWidth === 1440) {
        const boitePied = (sel) => doc.querySelector(sel)?.getBoundingClientRect() ?? null;
        // Le fond et les coins vivent sur le pseudo-élément : c'est lui qui
        // déborde sous le panneau pour masquer le visuel.
        const stylePied = styleOf(doc.querySelector(".site-footer"), "::before");
        assert(`pied : bloc d'appel à 48 (${boitePied(".footer-call")?.left.toFixed(1)})`,
            Math.abs((boitePied(".footer-call")?.left ?? -1) - 48) <= 1);
        assert(`pied : colonne de droite à 952 (${boitePied(".site-footer__aside")?.left.toFixed(1)})`,
            Math.abs((boitePied(".site-footer__aside")?.left ?? -1) - 952) <= 2);
        assert(`pied : coins arrondis en bas seulement (${stylePied.borderTopLeftRadius} / ${stylePied.borderBottomLeftRadius})`,
            stylePied.borderTopLeftRadius === "0px" && stylePied.borderBottomLeftRadius === "64px");
        assert(`pied : logo de 80 (${boitePied(".site-footer__logo")?.width.toFixed(0)})`,
            Math.abs((boitePied(".site-footer__logo")?.width ?? -1) - 80) <= 1);
    }

    /* --------------------------------------------------------------------- *
     * L'ouverture ADOUCIE du panneau d'accordéon.
     *
     * La campagne force `prefers-reduced-motion`, où la transition est
     * neutralisée : la lire sur l'état calculé ne dirait donc rien de ce que
     * voit un visiteur ordinaire. On lit la RÈGLE, et on éprouve à côté que la
     * neutralisation, elle, est bien celle qui s'applique ici.
     *
     * Le contrat d'accessibilité n'est pas touché : le panneau reste un vrai
     * `hidden`, ce que garde l'assertion « le second clic retire le texte de
     * l'arbre » plus haut.
     * --------------------------------------------------------------------- */
    if (doc.querySelector(".accordion__panel") !== null) {
        const transition = trouverRegle(doc, ".accordion__panel", (regle) => {
            const valeur = regle.style.transition;

            return valeur !== "" && valeur !== "none" ? valeur : null;
        });

        assert(
            `accordéon : ouverture adoucie (${transition})`,
            typeof transition === "string"
                && transition.includes("grid-template-rows")
                && transition.includes("opacity")
        );
        // `display` est une propriété DISCRÈTE : sans `allow-discrete` elle
        // saute d'un coup et la fermeture n'a pas lieu du tout.
        assert(
            "accordéon : la bascule de display est différée (allow-discrete)",
            typeof transition === "string" && transition.includes("allow-discrete")
        );
        // Le `display: grid` ne doit valoir QUE pour l'état ouvert : déclaré
        // sur les deux, il l'emporterait sur le `display: none` du navigateur
        // et le panneau ne se fermerait jamais.
        assert(
            "accordéon : le panneau fermé garde son display: none",
            styleOf(doc.querySelector(".accordion__panel[hidden]") ?? doc.body).display === "none"
        );
        assert(
            `accordéon : mouvement réduit, bascule instantanée (${styleOf(doc.querySelector(".accordion__panel")).transitionDuration})`,
            parseFloat(styleOf(doc.querySelector(".accordion__panel")).transitionDuration) === 0
        );
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
     * Animation de la navigation.
     *
     * Reprise de floema.com : l'élément survolé écarte ses voisins de 20px, en
     * sortant vite (0,3s, easeOutExpo) et en entrant avec un dépassement
     * (0,5s, easeOutBack). C'est ce dépassement qui donne le ressort.
     *
     * Les valeurs sont lues APRÈS neutralisation de la transition :
     * `getComputedStyle` rend la valeur ANIMÉE en cours, donc 0 tant qu'elle
     * n'a pas progressé — et son objet est VIVANT, il faut donc le relire après
     * chaque changement d'état. Les deux pièges ont fait échouer la mesure sur
     * du code juste.
     * --------------------------------------------------------------------- */
    const liensNav = Array.from(doc.querySelectorAll(".site-nav__list a"));

    // `affiche()` est déclaré plus bas dans le fichier : on teste la visibilité
    // sur place plutôt que d'avancer sa déclaration.
    const navVisible = liensNav.length >= 3
        && liensNav[1].getBoundingClientRect().width > 0
        && styleOf(liensNav[1]).visibility !== "hidden";

    if (navVisible) {
        const cible = liensNav[1];
        const reduit = win.matchMedia("(prefers-reduced-motion: reduce)").matches;

        assert(`navigation : aucun écart au repos (${styleOf(cible).marginLeft})`,
            styleOf(cible).marginLeft === "0px");

        cible.focus({ preventScroll: true });
        const dureeEntree = styleOf(cible).transitionDuration;
        const courbeEntree = styleOf(cible).transitionTimingFunction;
        cible.blur();

        // La campagne force `prefers-reduced-motion` : l'état animé n'est donc
        // jamais rendu, et le mesurer ici ne prouverait rien. On lit la RÈGLE
        // dans la feuille — elle, ne dépend d'aucune préférence.
        const regleSurvol = (() => {
            for (const feuille of Array.from(doc.styleSheets)) {
                let regles;

                try {
                    regles = feuille.cssRules;
                } catch (erreur) {
                    continue;
                }

                for (const regle of Array.from(regles || [])) {
                    if (regle.selectorText === ".site-nav__list a:hover, .site-nav__list a:focus-visible") {
                        return regle.style;
                    }
                }
            }

            return null;
        })();

        assert("navigation : la règle de survol existe", regleSurvol !== null);

        if (regleSurvol !== null) {
            assert(
                `navigation : écart de 20px de part et d'autre (${regleSurvol.margin})`,
                /^0px?\s+(1\.25rem|20px)$/.test(regleSurvol.margin)
            );
            assert(
                `navigation : entrée en 0,5s à dépassement (${regleSurvol.transition.slice(0, 58)})`,
                regleSurvol.transition.includes("0.5s")
                    && regleSurvol.transition.includes("cubic-bezier(0.175, 0.885, 0.32, 1.275)")
            );
        }

        const regleRepos = styleOf(cible);
        assert(
            `navigation : sortie en 0,3s (${regleRepos.transitionDuration})`,
            reduit || regleRepos.transitionDuration.includes("0.3s")
        );

        if (reduit) {
            cible.focus({ preventScroll: true });
            assert(
                `navigation : mouvement réduit, aucun écartement (${styleOf(cible).marginLeft})`,
                styleOf(cible).marginLeft === "0px"
            );
            cible.blur();
        }

        /* ----------------------------------------------------------------- *
         * La forme de la barre.
         *
         * Pastilles et collets sont tracés d'un seul `<path>` : ce qui se
         * vérifie n'est donc pas une largeur de chaînon, c'est la SILHOUETTE.
         * `isPointInFill` répond exactement — peint ou non — là où une lecture
         * de style ne dirait rien du dessin obtenu.
         * ----------------------------------------------------------------- */
        const nav = doc.querySelector(".site-nav");
        const forme = doc.querySelector(".site-nav__shape");
        const trace = forme === null ? null : forme.querySelector("path");

        assert("forme : servie par le serveur, décorative", forme !== null
            && forme.getAttribute("aria-hidden") === "true"
            && forme.getAttribute("focusable") === "false");

        // Sous le point de rupture la navigation devient un panneau vertical :
        // il n'y a plus de rangée, donc plus de barre à tracer.
        const enRangee = win.matchMedia("(min-width: 1025px)").matches;

        if (trace !== null && ! enRangee) {
            assert(
                "forme : retirée hors de la rangée horizontale",
                ! nav.classList.contains("site-nav--shaped")
            );
        }

        if (trace !== null && enRangee) {
            const relayer = () => win.dispatchEvent(new win.Event("resize"));
            const peint = (x, y) => trace.isPointInFill(new win.DOMPoint(x, y));

            // La forme reste sous mouvement réduit : elle ne bouge pas, elle
            // peint. Sans elle les liens seraient illisibles sur la photo.
            relayer();
            assert("forme : appliquée", nav.classList.contains("site-nav--shaped"));

            const mesurer = () => {
                relayer();

                const cadre = nav.getBoundingClientRect();
                const hauteur = liensNav[0].getBoundingClientRect().height;
                const boites = liensNav.map((lien) => {
                    const boite = lien.getBoundingClientRect();

                    return { gauche: boite.left - cadre.left, droite: boite.right - cadre.left };
                });
                let trous = 0;

                for (let x = boites[0].gauche + 0.5; x < boites[boites.length - 1].droite - 0.5; x += 0.5) {
                    if (! peint(x, hauteur / 2)) {
                        trous += 1;
                    }
                }

                const jonction = (boites[0].droite + boites[1].gauche) / 2;
                let haut = 0;
                let bas = hauteur;

                for (let y = 0; y < hauteur; y += 0.1) {
                    if (peint(jonction, y)) {
                        haut = y;
                        break;
                    }
                }

                for (let y = hauteur; y > 0; y -= 0.1) {
                    if (peint(jonction, y)) {
                        bas = y;
                        break;
                    }
                }

                return {
                    trous,
                    hauteur,
                    collet: bas - haut,
                    ecart: boites[1].gauche - boites[0].droite,
                    peintes: boites.filter((b) => peint((b.gauche + b.droite) / 2, hauteur / 2)).length,
                };
            };

            const repos = mesurer();

            assert(`forme : les ${liensNav.length} pastilles sont peintes (${repos.peintes})`,
                repos.peintes === liensNav.length);
            assert(`forme : silhouette continue au repos (${repos.trous} trous)`, repos.trous === 0);
            // Le collet ne se referme pas : c'est lui qui donne la continuité.
            // 61 % relevé, la référence est à 55 % pour ses propres proportions.
            assert(
                `forme : collet à ${(100 * repos.collet / repos.hauteur).toFixed(0)} % de la rangée au repos`,
                repos.collet > repos.hauteur * 0.5 && repos.collet < repos.hauteur * 0.75
            );

            // Écart forcé : la campagne neutralise le mouvement, or c'est
            // justement l'écart ouvert qui met le générateur à l'épreuve.
            const forcage = doc.createElement("style");

            forcage.textContent = ".site-nav__list li:nth-child(2) a { margin: 0 20px !important; }";
            doc.head.appendChild(forcage);

            const etire = mesurer();

            assert(`forme : écart ouvert de ${etire.ecart.toFixed(0)}px`, etire.ecart > 19);
            assert(`forme : silhouette continue une fois étirée (${etire.trous} trous)`,
                etire.trous === 0);
            // Le collet s'affine en s'étirant — c'est le filament de la
            // référence — mais il ne se pince jamais jusqu'à rompre.
            assert(
                `forme : collet aminci à ${(100 * etire.collet / etire.hauteur).toFixed(0)} % (${repos.collet.toFixed(1)} → ${etire.collet.toFixed(1)})`,
                etire.collet < repos.collet && etire.collet > etire.hauteur * 0.3
            );

            forcage.remove();
            relayer();
        }

        if (reduit && enRangee) {
            // L'écartement disparaît : sans lui, plus aucun signal de survol.
            const regleSoulignee = trouverRegle(doc, ".site-nav__list a:hover", (regle) =>
                regle.style.textDecoration !== "" ? regle.style.textDecoration : null);

            assert(
                `forme : survol souligné sous mouvement réduit (${regleSoulignee})`,
                typeof regleSoulignee === "string" && regleSoulignee.includes("underline")
            );
        }

        // La dernière entrée anime ses voisines de gauche comme les autres, mais
        // n'ouvre RIEN à sa droite : elle y borde le bouton d'action, qui
        // n'appartient pas au menu. Lu dans la règle, la campagne forçant le
        // mouvement réduit.
        const margesDerniere = trouverRegle(doc, "li:last-child a:hover", (regle) => {
            const gauche = regle.style.marginLeft;

            return gauche === "" ? null : `${gauche}|${regle.style.marginRight}`;
        });

        assert(
            `navigation : la dernière entrée s'écarte vers la GAUCHE (${margesDerniere})`,
            margesDerniere === "1.25rem|0px" || margesDerniere === "20px|0px"
        );

        /* ------------------------------------------------------------------ *
         * La PUCE de l'entrée courante.
         *
         * Le contenu de démonstration n'a pas de page courante — ses entrées
         * sont des liens personnalisés — donc la classe est posée à la main,
         * comme WordPress le ferait. Sans cela l'assertion ne pourrait pas
         * échouer : il n'y aurait rien à mesurer.
         * ------------------------------------------------------------------ */
        const premiere = doc.querySelector(".site-nav__list li");

        if (premiere !== null) {
            const round = (valeur) => Math.round(valeur);
            const lien = premiere.querySelector("a");
            const largeurSansPuce = lien.getBoundingClientRect().width;

            premiere.classList.add("current-menu-item");

            const puce = styleOf(lien, "::after");
            const largeurAvecPuce = lien.getBoundingClientRect().width;

            assert(
                `navigation : puce de 12px sur l'entrée courante (${puce.width} × ${puce.height}, ${puce.borderRadius})`,
                puce.content !== "none" && puce.width === "12px" && puce.height === "12px"
                    && parseFloat(puce.borderRadius) >= 6
            );
            // La teinte vient des RÉGLAGES du site, pas du thème : on éprouve
            // que la classe de la navigation la pilote, et que les deux choix
            // proposés au contributeur donnent bien deux couleurs distinctes.
            // Sans ce second point, une propriété jamais lue passerait.
            const nav = doc.querySelector(".site-nav");
            const classeNav = nav.className;
            const teinteDe = (couleur) => {
                nav.className = `site-nav site-nav--dot-${couleur}`;

                return styleOf(lien, "::after").backgroundColor;
            };
            const rouge = teinteDe("orange");
            const vert = teinteDe("turquoise");
            // Repli de la feuille de style : réglages vides ou ACF absent, la
            // classe de teinte manque et la puce doit rester « Rouge ».
            //
            // C'est CE repli qui est éprouvable, pas la teinte peinte sur la
            // page : celle-ci vient de la base de données du site visité, et
            // l'affirmer reviendrait à recetter un contenu.
            nav.className = "site-nav";
            const repli = styleOf(lien, "::after").backgroundColor;
            nav.className = classeNav;

            assert(
                `navigation : repli « Rouge » sans classe de teinte (${repli})`,
                repli === "rgb(226, 83, 4)"
            );
            assert(
                `navigation : le réglage pilote la teinte (rouge ${rouge} / vert ${vert})`,
                rouge === "rgb(226, 83, 4)" && vert === "rgb(4, 139, 140)"
            );
            // `order: -1` la place AVANT le libellé, alors qu'elle est en
            // `::after` — le `::before` porte déjà la zone de survol étendue.
            assert(
                `navigation : puce placée avant le libellé (order ${puce.order})`,
                styleOf(lien).display === "flex" && puce.order === "-1"
            );
            // Elle occupe de la place : 12 de puce et 8 d'écart. Une puce en
            // position absolue, superposée au libellé, passerait les mesures
            // ci-dessus sans rien pousser.
            //
            // Mesurable en RANGÉE seulement : en colonne, les entrées sont
            // étirées à la largeur du panneau, et c'est l'écart permanent de
            // l'entrée courante qui pilote alors leur largeur — la puce n'y
            // change rien.
            if (win.innerWidth > 1024) {
                assert(
                    `navigation : la puce élargit la pastille de 20px (${round(largeurAvecPuce - largeurSansPuce)})`,
                    Math.abs(largeurAvecPuce - largeurSansPuce - 20) < 1
                );
            }

            premiere.classList.remove("current-menu-item");
        }

        // Le bouton d'action ne porte pas l'animation : il flotte à côté du
        // menu, sur son propre emplacement.
        const boutonAction = doc.querySelector(".site-header__cta a");

        if (boutonAction !== null) {
            boutonAction.focus({ preventScroll: true });
            assert(
                `navigation : « Prendre RDV » reste immobile (${styleOf(boutonAction).marginLeft})`,
                styleOf(boutonAction).marginLeft === "0px"
            );
            boutonAction.blur();
        }

        // Zone de survol débordant la pastille : l'écartement part avant que le
        // curseur touche le lien.
        assert(
            "navigation : zone de survol étendue",
            win.getComputedStyle(cible, "::before").content !== "none"
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
    // Une photo en plein cadre rend la mesure tout aussi impossible qu'une
    // image de fond CSS — mais elle arrive par un `<img>`, pas par une
    // déclaration. C'est le montage des cartes de technologie : le visuel est
    // un `position: absolute; inset: 0` posé DERRIÈRE le titre, donc invisible
    // à une remontée qui ne lit que `background-image`.
    //
    // Sans ce cas, la campagne mesurait le titre contre l'aplat de repli de la
    // carte — un aplat que la photo recouvrait entièrement. Elle passait donc
    // en mesurant une couleur que personne ne voit.
    // Un fond que le contrôleur ne peut pas lire : une image ou un SVG posé
    // par-dessus. On rend alors `null` — « indécidable » — plutôt qu'une
    // couleur fausse.
    //
    // Le SVG a été ajouté pour la barre de navigation : quand le script trace
    // sa silhouette, les pastilles perdent leur fond blanc et c'est le `<path>`
    // qui peint. Remonter les ancêtres atterrissait alors sur le `body` et
    // annonçait du bleu sur bleu. Même cas que les images du hero, un élément
    // près.
    const couvertParUnDessin = (node) => {
        const boite = node.getBoundingClientRect();
        const dessins = [...node.querySelectorAll("img, svg")];

        return dessins.some((dessin) => {
            const cs = styleOf(dessin);

            if (cs.position !== "absolute" && cs.position !== "fixed") {
                return false;
            }

            const cadre = dessin.getBoundingClientRect();

            return cadre.width >= boite.width - 1 && cadre.height >= boite.height - 1;
        });
    };
    // Le fond effectif : on remonte jusqu'à un aplat opaque. Une image de fond
    // rend la mesure impossible — on écarte plutôt que de deviner.
    // Le fond peint par un PSEUDO-ÉLÉMENT couvrant. Le panneau du pied de page
    // porte le sien sur un `::before` en absolu calé sur les quatre bords —
    // remonter les ancêtres ne le voyait pas, et le contrôle atterrissait sur
    // le `body`. Blanc, il passait par chance ; coloré, il annonçait 1,00:1 sur
    // 26 éléments alors que le texte est parfaitement lisible sur son panneau.
    //
    // Le `bottom` n'est PAS exigé à zéro : le panneau du pied de page déborde
    // volontairement par le bas pour masquer le visuel révélé, et un débord
    // couvre l'élément tout autant.
    const fondDuPseudo = (node) => {
        const cs = styleOf(node, "::before");

        if (cs.content === "none" || cs.position !== "absolute") {
            return null;
        }

        if (cs.top !== "0px" || cs.left !== "0px" || cs.right !== "0px") {
            return null;
        }

        const fond = couleur(cs.backgroundColor);

        return fond !== null && fond.a === 1 ? fond.rgb : null;
    };

    const fondDe = (node) => {
        let courant = node;

        while (courant !== null && courant.nodeType === 1) {
            const cs = styleOf(courant);

            if (cs.backgroundImage !== "none" || couvertParUnDessin(courant)) {
                return null;
            }

            const fond = couleur(cs.backgroundColor);

            if (fond !== null && fond.a === 1) {
                return fond.rgb;
            }

            const pseudo = fondDuPseudo(courant);

            if (pseudo !== null) {
                return pseudo;
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

        // `.skip-link` est l'AUTRE motif de masquage du thème, à côté de
        // `.screen-reader-text` : il pousse l'élément à `left: -9999px` au lieu
        // de le réduire à un pixel. Son texte n'est donc jamais lu là où il est
        // mesuré ici — il ne devient visible qu'à la prise de focus, où il
        // reçoit un fond blanc. C'est cet état-là qui compte, et il est éprouvé
        // juste après la boucle.
        const masque = node.closest(".screen-reader-text") !== null
            || node.closest(".skip-link") !== null;

        if (texte === "" || !affiche(node) || masque) {
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

    // Le lien d'évitement, exclu de la boucle ci-dessus parce qu'il est hors
    // écran, est éprouvé sur la RÈGLE de son état visible : à la prise de
    // focus il doit recevoir un fond OPAQUE, sans quoi son texte se poserait
    // sur ce qu'il recouvre — et ce serait le seul moment où on le lit.
    assert(
        "lien d'évitement : fond opaque à la prise de focus",
        trouverRegle(doc, ".skip-link", (regle) => {
            const fond = couleur(regle.style.backgroundColor);

            return fond !== null && fond.a === 1 ? true : null;
        }) === true
    );

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
