// /!\ CAUTION /!\
// This loads the css even though it is not use here do not remove it
import "../styles/app.scss"
// /!\ END OF CAUTION /!\

/**
 * Mobile header menu.
 *
 * The open state lives in a single place — the `is-menu-open` class on <body> —
 * which both the scroll lock and the panel visibility read from.
 *
 * The panel covers the page, so everything behind it is made `inert` while it
 * is open. Without that, tabbing out of the panel walked straight into hidden
 * content: measured at 320px, eleven controls behind the overlay were still in
 * the tab order — the logo, the hero card, the carousel and all five accordion
 * triggers. `inert` removes them from the tab order AND from the accessibility
 * tree in one attribute, which is exactly the pair of effects wanted here.
 */
const initHeaderMenu = () => {
    const toggle = document.querySelector(".site-header__toggle");
    const panel = document.getElementById("site-header-nav");

    if (toggle === null || panel === null) {
        return;
    }

    // On remonte du panneau jusqu'à <body> en neutralisant, à chaque étage, les
    // FRÈRES de la branche. Filtrer sur les enfants de <body> ne suffirait pas :
    // le logo est dans le même <header> que le panneau, il resterait tabulable.
    // Le bouton est épargné puisque c'est lui qui referme.
    const aNeutraliser = () => {
        const cibles = [];
        let noeud = panel;

        while (noeud !== null && noeud !== document.body && noeud.parentElement !== null) {
            for (const frere of noeud.parentElement.children) {
                if (frere !== noeud && !frere.contains(toggle) && frere !== toggle) {
                    cibles.push(frere);
                }
            }

            noeud = noeud.parentElement;
        }

        return cibles;
    };

    const setOpen = (isOpen) => {
        toggle.setAttribute("aria-expanded", String(isOpen));
        document.body.classList.toggle("is-menu-open", isOpen);
        aNeutraliser().forEach((el) => { el.inert = isOpen; });
    };

    const isOpen = () => toggle.getAttribute("aria-expanded") === "true";

    toggle.addEventListener("click", () => setOpen(!isOpen()));

    panel.addEventListener("click", (event) => {
        if (event.target.closest("a") !== null) {
            setOpen(false);
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && isOpen()) {
            setOpen(false);
            toggle.focus();
        }
    });
};

// Déplacement au-delà duquel un appui devient un glissement. Sous ce seuil, le
// geste reste un clic.
const CAROUSEL_SEUIL_GLISSEMENT = 6;

// Un rail épinglé est piloté par le défilement de la PAGE : sa position ne
// s'écrit pas, elle se demande. La classe est posée par `initScrollRails`, donc
// ce prédicat est faux sans JavaScript d'épinglage, sous mouvement réduit, sous
// le point de rupture et sur un rail qui tient dans la vue.
const estEpingle = (carousel) => carousel.closest(".carousel-pin--active") !== null;

/**
 * Horizontal carousels.
 *
 * Scrolling itself is native — touch, trackpad, horizontal wheel and keyboard
 * all work without this file. What follows only adds the indicator, the two
 * buttons and mouse dragging on top of it, so a JavaScript failure degrades to
 * a plain scroller.
 */
const initCarousels = () => {
    // Le défilement animé est une préférence, pas un acquis : certains
    // utilisateurs le désactivent au niveau du système, et l'animation peut
    // provoquer un malaise vestibulaire.
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    const behavior = () => (reduceMotion.matches ? "auto" : "smooth");

    document.querySelectorAll("[data-carousel]").forEach((carousel) => {
        const rail = carousel.querySelector(".carousel__rail");
        const thumb = carousel.querySelector("[data-carousel-thumb]");
        const previous = carousel.querySelector("[data-carousel-prev]");
        const next = carousel.querySelector("[data-carousel-next]");

        if (rail === null) {
            return;
        }

        // La cible est suivie en JavaScript plutôt que déléguée à `scrollBy` :
        // deux clics rapprochés partiraient sinon de la même position courante,
        // l'animation n'ayant pas progressé, et le second remplacerait le
        // premier au lieu de s'y ajouter.
        let target = null;

        const update = () => {
            const total = rail.scrollWidth;
            const visible = rail.clientWidth;
            const furthest = total - visible;

            if (thumb !== null) {
                const width = total === 0 ? 100 : Math.min(100, (visible / total) * 100);
                const offset = total === 0 ? 0 : (rail.scrollLeft / total) * 100;
                thumb.style.setProperty("--thumb-width", `${width}%`);
                thumb.style.setProperty("--thumb-offset", `${offset}%`);
            }

            // Une tolérance d'un pixel : scrollLeft est fractionnaire dès que la
            // page est zoomée, et n'atteint jamais exactement sa borne.
            if (previous !== null) {
                previous.disabled = rail.scrollLeft <= 1;
            }

            if (next !== null) {
                next.disabled = rail.scrollLeft >= furthest - 1;
            }

            // Le curseur « main » ne s'affiche que s'il y a réellement quelque
            // chose à tirer : sur un rail qui tient dans la vue, il promettrait
            // un geste sans effet. Et sur un rail SANS FLÈCHES non plus — le
            // glissement y est refusé, faute de l'alternative qu'exige le WCAG
            // 2.5.7, donc le curseur promettrait un geste inopérant.
            //
            // La même condition qu'au `pointerdown`, et c'est voulu : une
            // promesse visuelle qui survivrait au retrait du geste serait pire
            // que pas de curseur du tout.
            carousel.classList.toggle(
                "carousel--draggable",
                furthest > 1 && previous !== null && next !== null,
            );

            // Cible atteinte : on rend la main au défilement de l'utilisateur.
            if (target !== null && Math.abs(rail.scrollLeft - target) < 2) {
                target = null;
            }
        };

        const scrollByPage = (direction, depuis = null) => {
            const furthest = rail.scrollWidth - rail.clientWidth;
            const from = depuis ?? (target === null ? rail.scrollLeft : target);
            target = Math.max(0, Math.min(furthest, from + direction * rail.clientWidth));
            rail.scrollTo({ left: target, behavior: behavior() });
            // L'évènement `scroll` n'arrive qu'au tick suivant : sans cet appel,
            // l'état des boutons accuse un retard visible sur le clic.
            update();
        };

        if (previous !== null) {
            previous.addEventListener("click", () => scrollByPage(-1));
        }

        if (next !== null) {
            next.addEventListener("click", () => scrollByPage(1));
        }

        // Glisser-déposer à la souris.
        //
        // Réservé au pointeur SOURIS. Au doigt, le défilement natif porte déjà
        // l'inertie et le rebond ; capter le geste tactile ferait surtout perdre
        // le défilement VERTICAL de la page dès que le doigt part de travers,
        // puisque le rail décide alors seul de ce que devient le mouvement.
        //
        // Les flèches, le clavier et la molette restent les chemins d'origine :
        // le glissement s'ajoute, il ne remplace rien. C'est ce qui satisfait le
        // critère WCAG 2.5.7, qui exige une alternative à tout geste de
        // glissement.
        let origine = null;
        let aGlisse = false;

        rail.addEventListener("pointerdown", (event) => {
            // Remis à plat AVANT le filtre : un `click` retardé du geste
            // précédent ne doit pas être avalé par celui-ci.
            aGlisse = false;

            // Le glissement n'existe QUE là où une alternative existe, et
            // cette alternative, ce sont les flèches — WCAG 2.5.7 exige un
            // équivalent à tout geste de glissement. Un carrousel rendu sans
            // flèches n'a donc pas de glissement : la règle est déduite de leur
            // absence plutôt que passée en argument, ce qui rend les deux
            // impossibles à désynchroniser.
            //
            // Sur un rail épinglé, le glissement n'aurait de toute façon aucun
            // effet : la position appartient au défilement de la page, qui la
            // réécrirait à l'image suivante.
            const sansAlternative = previous === null || next === null;

            if (event.pointerType === "touch" || event.button !== 0
                || sansAlternative || estEpingle(carousel)) {
                return;
            }

            origine = { x: event.clientX, scroll: rail.scrollLeft };
            // Le geste prend la main sur un défilement animé en cours.
            target = null;
        });

        const relacher = () => {
            const depart = origine;
            // Lu AVANT de retirer la classe : le calage revient avec elle et
            // ramène aussitôt le rail à son point de départ.
            const sens = depart === null ? 0 : Math.sign(rail.scrollLeft - depart.scroll);

            origine = null;
            carousel.classList.remove("carousel--dragging");

            // Une page par élément : laissé au calage, le rail ne changerait
            // d'élément qu'au-delà d'une demi-largeur d'écran traînée.
            if (depart !== null && aGlisse && carousel.hasAttribute("data-carousel-paged")) {
                target = null;
                scrollByPage(sens, depart.scroll);
            }
        };

        // Sur le DOCUMENT et non sur le rail : le curseur sort du rail bien
        // avant la fin du geste, et un `pointerup` relâché ailleurs ne serait
        // jamais entendu — le rail resterait collé au curseur.
        //
        // `setPointerCapture` aurait pu s'en charger, mais elle redirige aussi
        // les évènements des boutons de carte vers le rail : le clic n'arrivait
        // plus.
        document.addEventListener("pointermove", (event) => {
            if (origine === null) {
                return;
            }

            // Bouton relâché hors de la fenêtre : le `pointerup` n'est jamais
            // arrivé. Le premier mouvement au retour rattrape l'oubli.
            if (event.buttons === 0) {
                relacher();

                return;
            }

            const parcouru = event.clientX - origine.x;

            // Sous le seuil, le geste reste un clic : sans lui, le moindre
            // tremblement de souris sur le bouton d'une carte empêcherait de
            // l'ouvrir.
            if (! aGlisse && Math.abs(parcouru) < CAROUSEL_SEUIL_GLISSEMENT) {
                return;
            }

            if (! aGlisse) {
                aGlisse = true;
                carousel.classList.add("carousel--dragging");
                // La sélection a commencé au `pointerdown`, avant qu'on sache
                // qu'il s'agissait d'un glissement : `user-select` ne la
                // défait pas, il empêche seulement de l'étendre.
                document.getSelection()?.removeAllRanges();
            }

            rail.scrollLeft = origine.scroll - parcouru;
        });

        document.addEventListener("pointerup", relacher);
        document.addEventListener("pointercancel", relacher);

        // Un `<img>` démarre un glisser-déposer NATIF au bout de quelques
        // pixels, ce qui annule le nôtre par un `pointercancel`.
        rail.addEventListener("dragstart", (event) => {
            if (origine !== null) {
                event.preventDefault();
            }
        });

        // Le relâchement est suivi d'un `click` sur ce qui se trouve sous le
        // curseur : sans ceci, un glissement terminé sur le bouton d'une carte
        // en ouvrirait le panneau. En capture, pour devancer le gestionnaire de
        // la carte.
        rail.addEventListener("click", (event) => {
            if (! aGlisse) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
        }, true);

        rail.addEventListener("scroll", update, { passive: true });
        window.addEventListener("resize", update);
        update();
    });
};

/**
 * Disclosure panels: the treatments accordion AND the technology cards.
 *
 * Selected by `data-disclosure` rather than by a component class: both use the
 * exact same contract — a button carrying aria-expanded and aria-controls, and
 * a panel that is really `hidden` rather than merely invisible. Two copies of
 * this loop would have drifted apart.
 *
 * Several panels may be open at once. The mockup shows a single one open, but
 * that reads as a demonstration of the open state rather than a rule — and
 * closing a panel the visitor did not ask to close is worse than a long page.
 */
/**
 * Panneaux dépliables : UN SEUL ouvert à la fois par groupe.
 *
 * Le groupe est déclaré par `data-disclosure-group` sur un ancêtre, et jamais
 * deviné : la liste de l'accordéon le porte, la section des technologies aussi.
 * Deux conséquences voulues — replier une carte de technologie ne touche pas
 * l'accordéon des traitements, et un panneau posé hors de tout groupe reste
 * indépendant plutôt que de fermer le reste de la page par surprise.
 *
 * La règle est appliquée AU CHARGEMENT autant qu'au clic. Le champ « ouvert »
 * est contribuable : rien n'empêche d'en cocher deux dans l'administration, et
 * la page s'ouvrirait alors dans un état que le premier clic ne rattraperait
 * pas.
 */
const basculerPanneau = (trigger, ouvrir) => {
    const panel = document.getElementById(trigger.getAttribute("aria-controls"));

    if (panel === null) {
        return;
    }

    trigger.setAttribute("aria-expanded", String(ouvrir));
    panel.hidden = ! ouvrir;
    // La carte de technologie masque son titre quand le panneau est ouvert :
    // l'état vit sur la carte, pas sur le bouton.
    trigger.closest(".tech-card")?.classList.toggle("tech-card--open", ouvrir);
};

// Referme les voisins du même groupe. Hors groupe, il n'y a personne à
// refermer : le panneau vit seul.
const replierVoisins = (trigger) => {
    const groupe = trigger.closest("[data-disclosure-group]");

    if (groupe === null) {
        return;
    }

    for (const autre of groupe.querySelectorAll("[data-disclosure]")) {
        if (autre !== trigger && autre.getAttribute("aria-expanded") === "true") {
            basculerPanneau(autre, false);
        }
    }
};

const initAccordions = () => {
    const declencheurs = [...document.querySelectorAll("[data-disclosure]")];

    for (const trigger of declencheurs) {
        if (document.getElementById(trigger.getAttribute("aria-controls")) === null) {
            continue;
        }

        if (trigger.getAttribute("aria-expanded") === "true") {
            replierVoisins(trigger);
        }

        trigger.addEventListener("click", () => {
            const estOuvert = trigger.getAttribute("aria-expanded") === "true";

            if (! estOuvert) {
                replierVoisins(trigger);
            }

            basculerPanneau(trigger, ! estOuvert);
        });
    }
};

/**
 * Hauteur UTILE d'une section : celle au bout de laquelle le volet suivant doit
 * commencer à la recouvrir.
 *
 * Elle vaut la boîte MOINS LE CHEVAUCHEMENT. Chaque section chevauche la
 * précédente de son rayon — sans quoi l'encoche des coins arrondis laisserait
 * voir deux oreilles claires. Le haut de la suivante est donc toujours 48px
 * au-dessus du bas de celle-ci, et une section figée sur sa BOÎTE laissait voir
 * ces 48px avant même d'avoir fini. Retour de recette, trois fois, sur trois
 * sections différentes.
 *
 * Ces 48px ne sont pas du contenu : ils vivent dans le rembourrage bas, que la
 * section suivante recouvre de toute façon. Les déduire ne cache rien — ça
 * avance seulement le figeage du moment où le volet ENTRE dans la vue, si bien
 * qu'il part de zéro sur une section immobile.
 *
 * LE RAYON, et non une constante : c'est la même valeur qui sert de marge
 * négative entre deux sections. Les écrire deux fois les laisserait diverger, et
 * le volet réapparaîtrait au premier changement d'arrondi.
 *
 * La galerie épinglée y ajoute un rembourrage bas EXPLICITE de la même valeur,
 * pour que sa fin de course tombe elle aussi sur ce point. Voir
 * pages/homepage.scss.
 *
 * S'y ajoute `--volet-delai`, qui fait partir le volet de PLUS BAS ENCORE : la
 * section se fige, et il reste ce nombre de pixels avant qu'il n'attaque. La
 * feuille de style le met à zéro sur les sections dont le défilement pilote déjà
 * quelque chose — là, ce même écart avait été signalé comme un défaut.
 *
 */
const hauteurUtile = (section) => {
    const style = window.getComputedStyle(section);

    return section.offsetHeight
        - (parseFloat(style.borderTopLeftRadius) || 0)
        - (parseFloat(style.getPropertyValue("--volet-delai")) || 0);
};

/* ------------------------------------------------------------------------- *
 * La silhouette d'une rangée de pastilles.
 *
 * Sert la barre de navigation et les boutons d'action primaires. Les pastilles
 * et les collets qui les relient sont tracés d'un seul `<path>` peint derrière
 * elles. C'est ce qui donne la CONTINUITÉ : un chaînon posé dans l'écart
 * resterait un second fond, et deux fonds qui se touchent laissent toujours une
 * couture. Ici il n'y a qu'une silhouette.
 *
 * Relevé sur la référence (floema.com), sur une rangée de 36,80 de haut pour un
 * arrondi de 12,51 : le collet s'accroche à 19,4° sur l'arrondi — donc il en
 * mange la plus grande part — et ses arêtes repartent tangentiellement, sans
 * angle. C'est cet angle, et non une largeur de chaînon, qui fixe la taille de
 * guêpe : elle vaut ici 63 % de la hauteur de la rangée.
 * ------------------------------------------------------------------------- */

const PILL_ANGLE_ACCROCHE = (19.4 * Math.PI) / 180;

// Longueur des poignées de Bézier, en fraction de l'arrondi. Elle vaut la
// moitié de l'écart tant que celui-ci est petit — c'est le rapport relevé sur
// la référence — puis PLAFONNE. Sans ce plafond, un écart de 20px enverrait les
// poignées si loin que les deux arêtes se croiseraient et fermeraient le collet.
const PILL_POIGNEE_MAX = 0.5;

const cheminSilhouette = (boites, rayon, hauteur) => {
    const dx = rayon * (1 - Math.cos(PILL_ANGLE_ACCROCHE));
    const dy = rayon * (1 - Math.sin(PILL_ANGLE_ACCROCHE));
    // Tangente unitaire à l'arrondi au point d'accroche.
    const tx = Math.sin(PILL_ANGLE_ACCROCHE);
    const ty = Math.cos(PILL_ANGLE_ACCROCHE);
    const dernier = boites.length - 1;
    const arc = (x, y) => `A${rayon} ${rayon} 0 0 1 ${x} ${y}`;
    const poignee = (ecart) => Math.min(ecart / 2, rayon * PILL_POIGNEE_MAX);
    const d = [`M${boites[0].gauche + rayon} 0`];

    // Arête supérieure, de gauche à droite.
    for (let i = 0; i < boites.length; i += 1) {
        d.push(`L${boites[i].droite - rayon} 0`);

        if (i === dernier) {
            break;
        }

        const ax = boites[i].droite - dx;
        const bx = boites[i + 1].gauche + dx;
        const lg = poignee(bx - ax);

        d.push(arc(ax, dy));
        d.push(`C${ax + lg * tx} ${dy + lg * ty}, ${bx - lg * tx} ${dy + lg * ty}, ${bx} ${dy}`);
        d.push(arc(boites[i + 1].gauche + rayon, 0));
    }

    d.push(arc(boites[dernier].droite, rayon));
    d.push(`L${boites[dernier].droite} ${hauteur - rayon}`);
    d.push(arc(boites[dernier].droite - rayon, hauteur));

    // Arête inférieure, de droite à gauche : les mêmes collets, retournés.
    for (let i = dernier; i > 0; i -= 1) {
        const ax = boites[i].gauche + dx;
        const bx = boites[i - 1].droite - dx;
        const lg = poignee(ax - bx);
        const y = hauteur - dy;

        d.push(`L${boites[i].gauche + rayon} ${hauteur}`);
        d.push(arc(ax, y));
        d.push(`C${ax - lg * tx} ${y - lg * ty}, ${bx + lg * tx} ${y - lg * ty}, ${bx} ${y}`);
        d.push(arc(boites[i - 1].droite - rayon, hauteur));
    }

    d.push(`L${boites[0].gauche + rayon} ${hauteur}`);
    d.push(arc(boites[0].gauche, hauteur - rayon));
    d.push(`L${boites[0].gauche} ${rayon}`);
    d.push(arc(boites[0].gauche + rayon, 0));

    return `${d.join(" ")} Z`;
};

/**
 * Trace la silhouette d'une rangée de pastilles, et la suit pendant qu'elle
 * s'écarte.
 *
 * Sert la barre de navigation ET les boutons d'action primaires : dans les deux
 * cas des pastilles alignées sur une rangée, reliées par des collets, dont
 * l'écart s'ouvre au survol par une transition de `margin`.
 *
 *   racine     L'élément qui porte le SVG, les pastilles et `position: relative`.
 *   forme      Sélecteur du SVG décoratif, cherché dans la racine.
 *   pastilles  Sélecteur des boîtes à relier, dans l'ORDRE du document — qui est
 *              ici l'ordre visuel, celui que `cheminSilhouette` suppose.
 *   classe     Classe posée sur la racine tant que la silhouette est tracée.
 *   actif      Prédicat facultatif : rend la main sans rien tracer quand il est
 *              faux.
 *
 * Renvoie sa fonction de mise à jour, pour la rebrancher sur d'autres signaux.
 */
const initPillShape = ({ racine, forme, pastilles, classe, actif = () => true }) => {
    const svg = racine.querySelector(forme);
    const trace = svg === null ? null : svg.querySelector("path");

    if (trace === null) {
        return () => {};
    }

    const update = () => {
        const elements = Array.from(racine.querySelectorAll(pastilles));

        if (elements.length === 0 || ! actif()) {
            racine.classList.remove(classe);

            return;
        }

        const cadre = racine.getBoundingClientRect();
        const boites = elements.map((element) => {
            const boite = element.getBoundingClientRect();

            return {
                gauche: boite.left - cadre.left,
                droite: boite.right - cadre.left,
                haut: boite.top - cadre.top,
                hauteur: boite.height,
            };
        });

        // Une rangée, et une seule : à 200 % de taille de texte les pastilles
        // passent à la ligne, et un tracé qui suppose une bande unique peindrait
        // alors un bloc plein en travers.
        const hauteur = boites[0].hauteur;
        const alignees = boites.every(
            (boite) => Math.abs(boite.haut) < 0.5 && Math.abs(boite.hauteur - hauteur) < 0.5,
        );

        if (! alignees || hauteur <= 0) {
            racine.classList.remove(classe);

            return;
        }

        const rayon = parseFloat(window.getComputedStyle(elements[0]).borderTopLeftRadius) || 0;

        svg.setAttribute("viewBox", `0 0 ${cadre.width} ${hauteur}`);
        trace.setAttribute("d", cheminSilhouette(boites, rayon, hauteur));
        racine.classList.add(classe);
    };

    // L'écartement est une transition de marge : la forme doit la suivre image
    // par image. Plutôt que de compter les transitions ouvertes — un
    // `transitionend` manqué laisserait la boucle tourner sans fin — chaque
    // départ repousse une échéance, et la boucle s'arrête d'elle-même après.
    let echeance = 0;
    let enCours = false;

    const boucle = () => {
        update();

        if (performance.now() < echeance) {
            window.requestAnimationFrame(boucle);

            return;
        }

        enCours = false;
    };

    // Posé sur la RACINE : les événements de transition remontent, un seul
    // écouteur couvre donc toutes les pastilles quelle que soit leur profondeur.
    racine.addEventListener("transitionstart", (event) => {
        if (! String(event.propertyName).startsWith("margin")) {
            return;
        }

        echeance = performance.now() + 900;

        if (! enCours) {
            enCours = true;
            window.requestAnimationFrame(boucle);
        }
    });

    window.addEventListener("resize", update);

    // La largeur des pastilles dépend de la police : tracer avant qu'elle soit
    // chargée fige la forme sur les métriques de la police de secours.
    if (document.fonts !== undefined) {
        document.fonts.ready.then(update);
    }

    update();

    return update;
};

const initNavShape = () => {
    const nav = document.querySelector(".site-nav");

    if (nav === null) {
        return;
    }

    // Sous le point de rupture la navigation devient un panneau vertical : les
    // pastilles ne forment plus une rangée, il n'y a plus de barre à tracer.
    const rangee = window.matchMedia("(min-width: 1025px)");

    const update = initPillShape({
        racine: nav,
        forme: ".site-nav__shape",
        pastilles: ".site-nav__list a",
        classe: "site-nav--shaped",
        actif: () => rangee.matches,
    });

    rangee.addEventListener("change", update);
};

/**
 * Les boutons d'action primaires portent la même silhouette que le menu.
 *
 * Deux pastilles au lieu de cinq entrées, et un collet au lieu de quatre : le
 * tracé est le même, l'écart s'ouvre de la même marge. La variante secondaire
 * n'a qu'une pastille et aucun SVG — rien à faire pour elle.
 */
const initCtaShapes = () => {
    document.querySelectorAll(".cta--solid").forEach((cta) => {
        initPillShape({
            racine: cta,
            forme: ".cta__shape",
            pastilles: ".cta__icon, .cta__label",
            classe: "cta--shaped",
        });
    });
};

/**
 * Rail piloté par le défilement de la page.
 *
 * La bande reste collée le temps qu'une réserve s'épuise, et l'avancement dans
 * cette réserve DONNE la position horizontale du rail. Rien n'est confisqué :
 * la page défile normalement, le rail est une fonction de son avancement. C'est
 * ce qui rend le mouvement naturel et le laisse marcher au doigt, là où une
 * molette interceptée n'aurait touché ni le tactile ni le clavier.
 *
 * Relevé sur la référence donnée par le client — buildcover.com, le bloc
 * `module-mediaGroup` : le `pin-spacer` de GSAP y réserve 1212px et la course
 * horizontale du conteneur en mesure 1212. Le rapport est donc de 1:1, et c'est
 * la réserve qui le porte : elle vaut la course, aucun facteur à régler ici.
 *
 * Le rail passe en `overflow-x: hidden` : le script devient SEUL à écrire
 * `scrollLeft`. Deux écrivains sur la même position se disputeraient chaque
 * image — et c'est ce que fait la référence.
 *
 * Trois sorties de secours, comme la vue épinglée du parcours de soin : sous le
 * point de rupture, sous `prefers-reduced-motion` et sur un rail qui tient dans
 * la vue, l'épinglage n'est PAS posé et le carrousel reste celui de partout
 * ailleurs — défilement natif, clavier, glissement.
 */
const initScrollRails = () => {
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    // La bande occupe un écran entier : sous le point de rupture, elle mangerait
    // toute la vue d'un téléphone pour un rail qui s'y balaie déjà au doigt.
    const large = window.matchMedia("(min-width: 1025px)");

    document.querySelectorAll("[data-carousel-pinned]").forEach((carousel) => {
        const reserve = carousel.closest("[data-carousel-pin]");
        const rail = carousel.querySelector(".carousel__rail");

        if (reserve === null || rail === null) {
            return;
        }

        const course = () => rail.scrollWidth - rail.clientWidth;

        const update = () => {

            if (! reserve.classList.contains("carousel-pin--active")) {
                return;
            }

            // La course VERTICALE est mesurée, pas déduite de la course
            // horizontale : `100svh` et `innerHeight` divergent sur mobile, et
            // un avancement calculé sur la valeur attendue plutôt que sur la
            // hauteur réelle décalerait le rail de cet écart.
            const verticale = reserve.offsetHeight - window.innerHeight;

            if (verticale <= 0) {
                return;
            }

            const parcouru = -reserve.getBoundingClientRect().top;
            const avancement = Math.min(1, Math.max(0, parcouru / verticale));
            rail.scrollLeft = avancement * course();
        };

        const apply = () => {
            // `course()` est lue AVANT de poser la classe : elle reste juste en
            // `overflow-x: hidden`, mais la lire après aurait fait dépendre la
            // hauteur de la réserve d'un état qu'on vient de changer.
            const horizontale = course();
            const actif = ! reduceMotion.matches && large.matches && horizontale > 0;

            reserve.classList.toggle("carousel-pin--active", actif);

            if (! actif) {
                reserve.style.removeProperty("--pin-course");
                // Le rail redevient défilable : il lui faut son arrêt de
                // tabulation.
                rail.setAttribute("tabindex", "0");

                return;
            }

            reserve.style.setProperty("--pin-course", `${horizontale}px`);
            // Le rail n'est plus défilable par l'utilisateur : un arrêt de
            // tabulation qui ne défile rien serait un piège au clavier. Les
            // flèches, elles, sont de vrais boutons focalisables, et elles
            // pilotent le défilement de la page.
            rail.removeAttribute("tabindex");
            update();
        };

        // Écriture DIRECTE dans l'évènement, sans passer par une image.
        //
        // Le parcours de soin, lui, coalesce sur `requestAnimationFrame` parce
        // qu'il en fait davantage. Ici le travail par évènement se réduit à une
        // lecture de rectangle et une écriture de propriété — moins cher que la
        // comptabilité du planificateur, et Chrome cadence déjà `scroll` sur
        // l'image.
        //
        // Surtout, cette indirection ne tenait qu'à une image RENDUE. Mesuré en
        // campagne : `update` n'était appelé qu'une fois pour six évènements, la
        // dernière image demandée n'étant jamais produite, et le rail restait à
        // zéro. Une mécanique de défilement ne doit pas dépendre de ça.
        window.addEventListener("scroll", update, { passive: true });
        window.addEventListener("resize", apply);
        reduceMotion.addEventListener("change", apply);
        large.addEventListener("change", apply);

        // Aucune attente du chargement des visuels, contrairement à la
        // silhouette des pastilles qui attend la police : la course ne dépend
        // pas de ce qui se charge. Chaque élément du rail porte une largeur en
        // pixels issue de `LcdsMediaShape`, et le rembourrage de bout est un
        // jeton — `scrollWidth` est donc juste dès la première mise en page.
        apply();
    });
};

/**
 * Hauteur de l'en-tête, publiée pour les blocs qui doivent l'éviter.
 *
 * L'en-tête collé est du CSS pur (`sticky`, ou `fixed` au-dessus d'un hero) et
 * n'a besoin de rien d'ici. Ce qui est mesuré, c'est la SEULE chose que le CSS
 * ne peut pas connaître : sa hauteur réelle. Elle n'est pas déductible d'une
 * addition de jetons — l'en-tête revient à la ligne à fort grossissement de
 * texte, et sa hauteur double.
 *
 * L'en-tête reste TRANSPARENT sur toute la page, comme la maquette le dessine.
 * Aucun fond n'est posé au défilement : arbitré ainsi, il n'y a donc rien à
 * suivre et aucun gestionnaire de défilement ici.
 */
/**
 * Le volet : chaque section se fige une fois lue, et la suivante remonte
 * par-dessus elle.
 *
 * Le décalage de collage vaut « hauteur de la vue moins hauteur DE CETTE
 * section ». Aucune unité CSS ne désigne la hauteur de l'élément qui la porte,
 * d'où ce calcul en JavaScript — voir hero.scss pour les deux écritures CSS
 * essayées et pourquoi aucune ne convient.
 *
 * Une section plus haute que la vue reçoit une valeur NÉGATIVE : elle se fige
 * sur son dernier écran, ce qui est le comportement voulu.
 */
const initVolets = () => {
    const page = document.querySelector(".front-page");
    const hero = page === null ? null : page.querySelector(":scope > .hero");

    if (hero === null) {
        return;
    }

    const sections = [];

    for (let noeud = hero.nextElementSibling; noeud !== null; noeud = noeud.nextElementSibling) {
        sections.push(noeud);
    }

    // Position de FLUX de chaque section, relevée seulement quand elle n'est
    // pas collée. Une fois collée, plus rien ne la donne : son rectangle porte
    // le décalage, et `offsetTop` aussi — vérifié. La dernière valeur connue
    // reste juste tant que rien au-dessus d'elle ne change de hauteur, et
    // c'est exactement ce que l'observateur ci-dessous rappelle.
    const positions = new Map();

    // Aucun test de mouvement réduit ici : la règle qui colle les sections vit
    // déjà sous `prefers-reduced-motion: no-preference`. Le redoubler en
    // JavaScript ferait deux sources pour une même décision.
    // Une section ne joue le volet que si la SUIVANTE peut la recouvrir, donc
    // si celle-ci fait au moins une hauteur d'écran.
    //
    // Deux cas ne la remplissent pas : la DERNIÈRE, qui n'a personne derrière
    // elle, et celle qui précède une section dimensionnée par son visuel —
    // l'app mobile. Les figer se retournait contre le lecteur : on arrivait au
    // bas de « informations pratiques », et l'image la mangeait sur place au
    // lieu de la laisser partir. Mesuré : la dernière entrée n'était lisible
    // que sur 350px de défilement, à toutes les hauteurs de vue.
    const couvrable = (section) => {
        const suivante = section.nextElementSibling;

        return suivante !== null && suivante.offsetHeight >= window.innerHeight - 1;
    };

    // Le menu est FIXE sur une page qui porte un hero : il recouvre les 128
    // premiers pixels de la vue, et c'est exactement le rembourrage haut d'une
    // section — l'en-tête d'une section posée à 0 affleure donc sous lui.
    // `initHeaderHeight` publie la même mesure en CSS ; c'est le même nœud.
    const menu = document.getElementById("site-header");

    const mesurer = () => {
        const garde = menu === null ? 0 : menu.offsetHeight;

        for (const section of sections) {
            /*
             * Le décalage est BORNÉ par le menu ET par l'en-tête de la section.
             *
             * Une section à peine plus haute que la vue se fige quelques pixels
             * trop haut, et ces pixels-là passent entièrement sous le menu : son
             * étiquette et son bouton d'action y disparaissent sans qu'on ait
             * rien gagné à lire. Mesuré sur les technologies, vue de 900 : 27px
             * de course, étiquette figée à 101 pour un menu de 128.
             *
             * LE SEUL MENU NE SUFFIT PAS comme borne. Juste au-delà de sa
             * hauteur se trouvait le PIRE cas : la section se figeait d'un
             * poil plus qu'un menu, et son étiquette se rangeait exactement
             * derrière lui. Mesuré, et sans aucun rapport avec la cale de
             * retard — vue de 743, étiquette figée à -56 ; vue de 713, à -86.
             *
             * L'étiquette vit dans le rembourrage haut de la section : on exige
             * donc que la course dépasse le menu D'AU MOINS ce rembourrage. En
             * deçà, la section ne se fige pas du tout ; au-delà, l'étiquette est
             * franchement partie — parce qu'on a défilé dans la section, pas
             * parce qu'elle s'est figée.
             *
             * « Ne se fige pas » veut dire SANS `data-volet`, pas `top: 0`. Un
             * décalage nul la figeait en haut de la vue, et tout ce qui dépasse
             * passait sous le volet suivant sans avoir été lu. Mesuré sur les
             * traitements, vue de 1440 × 900 : le bouton d'action n'était
             * visible à AUCUNE position de défilement.
             */
            const entete = parseFloat(window.getComputedStyle(section).paddingTop) || 0;
            // Une section pilotée par le défilement se fige quand son TRAVAIL
            // est fini, pas quand sa boîte l'est — voir `hauteurUtile`. Se figer
            // sur la boîte laissait le volet et la section monter ENSEMBLE sur
            // les 48px de l'amorce, sans que rien ne soit recouvert.
            const course = window.innerHeight - hauteurUtile(section);
            const isBarelyTaller = course < 0 && course > -(garde + entete);
            const decalage = Math.min(course, 0);

            section.toggleAttribute("data-volet", couvrable(section) && ! isBarelyTaller);
            section.style.setProperty("--volet-top", `${decalage}px`);

            if (section.getBoundingClientRect().top > decalage + 1) {
                positions.set(section, section.getBoundingClientRect().top + window.scrollY);
            }
        }

        page.classList.add("front-page--volet");
    };

    // Une section figée est INVISIBLE — les volets suivants la recouvrent
    // entièrement — mais ses liens et ses boutons restent dans l'ordre de
    // tabulation. Le navigateur ne les fait pas défiler : leur rectangle est
    // déjà dans la vue, il n'a aucune notion de « recouvert ». Mesuré à 12000
    // de défilement : les six commandes de la section des traitements étaient
    // focalisables et masquées.
    //
    // On ramène donc la page dans la course de la section avant que le focus
    // s'y pose. WCAG 2.4.11.
    const revelerAuFocus = (event) => {
        const section = sections.find((noeud) => noeud.contains(event.target));
        const haut = section === undefined ? undefined : positions.get(section);

        if (haut === undefined) {
            return;
        }

        const fin = haut + section.offsetHeight - window.innerHeight;

        if (window.scrollY <= fin) {
            return;
        }

        // Deux temps : le premier décolle la section, le second amène la
        // commande elle-même dans la vue. Tant que la section est collée, sa
        // géométrie est fausse et `scrollIntoView` seul ne mènerait nulle part.
        window.scrollTo({ top: fin, behavior: "instant" });
        event.target.scrollIntoView({ block: "nearest", behavior: "instant" });
    };

    // Une section change de hauteur sans que la fenêtre bouge : un accordéon
    // qu'on déplie, une carte de technologie qu'on ouvre. Le seul `resize` de
    // fenêtre laisserait alors un décalage faux, et la section se figerait au
    // mauvais endroit.
    if (window.ResizeObserver !== undefined) {
        const observateur = new window.ResizeObserver(mesurer);

        for (const section of sections) {
            observateur.observe(section);
        }
    }

    window.addEventListener("resize", mesurer);
    document.addEventListener("focusin", revelerAuFocus);
    mesurer();
};

/**
 * Logo de la page d'accueil : la marque se réduit, l'écrit s'efface, au fil du
 * défilement.
 *
 * MÊME PARTAGE QUE LE PARCOURS DE SOIN : ce code publie UN SEUL NOMBRE, et le
 * CSS en tire l'échelle, le glissement et l'opacité. Les tailles et les
 * distances vivent donc dans la feuille de style, à un seul endroit ; les
 * réécrire ici aurait créé une seconde source qui aurait fini par diverger.
 *
 * Le nombre DÉPASSE 1 en fin de course, et c'est le rebond : la courbe remonte
 * jusqu'à ~1,058 avant de redescendre exactement à 1. L'échelle passe donc sous
 * sa valeur d'arrivée — la marque se pose à 77 avant de revenir à 80 — ce qui
 * se lit comme un petit ressort. C'est le CSS qui borne l'opacité, seule
 * propriété qui ne supporte pas le dépassement.
 *
 * L'accroche est posée par le gabarit, jamais devinée ici : sans
 * `data-logo-scroll`, il n'y a rien à animer. Voir header.php.
 */
// Course de défilement sur laquelle l'animation se joue entièrement.
//
// La référence fournie donnait 260, jugée trop rapide en recette. Portée à 420,
// soit un peu moins d'un demi-écran : l'animation se termine largement avant que
// la bannière ne quitte la vue, elle ne déborde donc pas sur la première section.
const LOGO_COURSE = 420;

// Courbe du défilement, avec DÉPASSEMENT en fin de course.
//
// La référence donnait `cubic-bezier(0.65, 0, 0.35, 1)`, dont le départ est un
// temps mort suivi d'un rattrapage d'un coup. Celle-ci démarre plus tôt — c'est
// le « plus fluide » demandé — tout en restant plus LENTE en absolu, ce qui est
// le vrai sujet. Comparé en pixels de défilement, seule mesure qui ait un sens
// puisque la course a changé elle aussi :
//
//              mi-course     90 %
//   référence    130px      186px
//   celle-ci     170px      244px      soit environ 30 % plus lent
//
// La première version essayée, `(0.3, 0, 0.25, 1.35)`, était plus RAPIDE que la
// référence au départ — 50 % dès 119px — alors qu'elle paraissait plus douce à
// la lecture. Mesurer a tranché ; l'œil se serait trompé.
//
// Le quatrième nombre, au-delà de 1, produit le rebond : sommet mesuré à 1,0449
// pour x = 0,82, retour exact à 1. C'est lui qui le gouverne — le monter creuse
// le rebond, le ramener à 1 le supprime.
const LOGO_COURBE = [0.5, 0, 0.35, 1.3];

/**
 * Évalue un `cubic-bezier` comme le fait une fonction de transition CSS.
 *
 * Newton-Raphson sur x pour retrouver le paramètre, puis évaluation sur y. Le
 * résultat n'est PAS borné entre les extrémités : c'est ce qui laisse passer le
 * dépassement.
 */
const courbeVersFonction = ([x1, y1, x2, y2]) => {
    const a = (u, v) => 1 - 3 * v + 3 * u;
    const b = (u, v) => 3 * v - 6 * u;
    const c = (u) => 3 * u;
    const valeur = (t, u, v) => ((a(u, v) * t + b(u, v)) * t + c(u)) * t;
    const pente = (t, u, v) => 3 * a(u, v) * t * t + 2 * b(u, v) * t + c(u);

    return (x) => {
        if (x <= 0) {
            return 0;
        }

        if (x >= 1) {
            return 1;
        }

        let t = x;

        for (let essai = 0; essai < 8; essai += 1) {
            const p = pente(t, x1, x2);

            if (p === 0) {
                break;
            }

            t -= (valeur(t, x1, x2) - x) / p;
        }

        return valeur(t, y1, y2);
    };
};

const initLogoScroll = () => {
    const lockup = document.querySelector("[data-logo-scroll]");

    if (lockup === null) {
        return;
    }

    // C'EST LA FEUILLE DE STYLE QUI DÉCIDE. Elle coupe l'animation sous le point
    // de rupture tablette et sous mouvement réduit, et publie `--logo-anime` au
    // même endroit. Le script se contente de la lire : le point de rupture reste
    // écrit une seule fois, dans les variables SCSS.
    //
    // Relu sur redimensionnement seulement, jamais par image : `getComputedStyle`
    // force un recalcul de style, et le payer à chaque cran de molette se verrait.
    const lireDrapeau = () => window
        .getComputedStyle(lockup)
        .getPropertyValue("--logo-anime")
        .trim() !== "0";

    /*
     * L'ÉCRIT NE TIENT PAS À TOUTES LES LARGEURS, et c'est mesuré, pas deviné.
     *
     * Il n'a pas de point de rupture propre : ce qui décide, c'est le bord
     * gauche de la navigation, qui dépend de la longueur des entrées du menu.
     * Un contributeur qui ajoute une entrée déplace la limite — un nombre écrit
     * en dur ici serait faux le jour même.
     *
     * La mesure se fait AVEC LE PROGRÈS REMIS À ZÉRO, c'est-à-dire à la place
     * que l'écrit occupe déployé : le lire tel quel donnerait sa position
     * glissée, et le test dépendrait alors de l'endroit où l'on a défilé. Rien
     * n'est peint entre les deux, tout tient dans le même appel.
     *
     * `offsetLeft` / `offsetWidth` auraient évité ce détour en ignorant les
     * transformations — mais ce sont des propriétés de `HTMLElement`, et l'écrit
     * est un SVG. Elles y valent `undefined`, la comparaison portait sur `NaN`
     * et restait FAUSSE partout : le verrou ne se posait jamais. Mesuré.
     */
    const ecrit = lockup.querySelector(".site-logo__word");
    const nav = document.querySelector(".site-header__nav");

    const manqueDePlace = () => {
        if (ecrit === null || nav === null) {
            return false;
        }

        lockup.style.setProperty("--logo-progress", "0");

        return ecrit.getBoundingClientRect().right > nav.getBoundingClientRect().left;
    };

    const adoucir = courbeVersFonction(LOGO_COURBE);
    let actif = lireDrapeau();
    let image = 0;

    const mesurer = () => {
        if (! actif) {
            // On RETIRE la propriété au lieu d'y écrire 1 : la feuille de style
            // reprend alors la main, et c'est elle qui porte la valeur de repli.
            lockup.style.removeProperty("--logo-progress");

            return;
        }

        const brut = Math.min(1, Math.max(0, window.scrollY / LOGO_COURSE));

        lockup.style.setProperty("--logo-progress", String(adoucir(brut)));
    };

    const relire = () => {
        actif = lireDrapeau();
        // Retiré AVANT de mesurer : l'écrit doit être rendu pour qu'on puisse
        // lire sa largeur, et il ne l'est plus une fois l'attribut posé.
        lockup.removeAttribute("data-logo-serre");
        lockup.toggleAttribute("data-logo-serre", manqueDePlace());
        // `mesurer` repose la valeur du moment — ou retire la propriété et rend
        // la main à la feuille de style. C'est lui qui défait le zéro posé par
        // la mesure ci-dessus.
        mesurer();
    };

    // Le défilement émet bien plus souvent que le navigateur ne peint. On ANNULE
    // l'image en attente et on en redemande une, plutôt que de renoncer quand il
    // y en a déjà une : renoncer suppose qu'elle sera rendue, et une image jamais
    // produite bloquait alors la mise à jour pour de bon.
    const planifier = () => {
        window.cancelAnimationFrame(image);
        image = window.requestAnimationFrame(mesurer);
    };

    window.addEventListener("scroll", planifier, { passive: true });
    window.addEventListener("resize", relire);
    // Le mouvement réduit n'est pas un point de rupture du projet mais un réglage
    // du système : le suivre ici ne duplique aucune valeur de maquette.
    window.matchMedia("(prefers-reduced-motion: reduce)")
        .addEventListener("change", relire);

    // La largeur de la navigation dépend de la POLICE : mesurer avant qu'elle
    // soit chargée donne un menu trop étroit, donc une place disponible
    // surestimée, et l'écrit restait posé sur les liens. Constaté à 1280 —
    // l'attribut ne se posait pas alors que le recouvrement valait 148px.
    // `initHeaderHeight` porte le même garde-fou, pour la même raison.
    if (document.fonts !== undefined) {
        document.fonts.ready.then(relire);
    }

    relire();
};

/**
 * Publie la hauteur du titre de chaque section de conseils, pour que
 * l'étiquette du groupe vienne se caler JUSTE DESSOUS.
 *
 * MÊME PARTAGE QUE LE LOGO ET LE PARCOURS : ce code publie UN SEUL NOMBRE, et
 * la feuille de style en tire le calage. Le retrait sous l'en-tête, la
 * respiration entre le titre et l'étiquette, les tailles : tout cela reste dans
 * `block-advice.scss`, à un seul endroit.
 *
 * POURQUOI MESURER PLUTÔT QU'ADDITIONNER. Le titre revient à la ligne dès que
 * son libellé est long, et systématiquement à fort grossissement de texte. Une
 * hauteur écrite à la main rangerait alors l'étiquette DERRIÈRE lui — le défaut
 * ne se verrait pas à la taille de la maquette, et apparaîtrait chez le premier
 * contributeur qui écrit un titre de deux lignes. Même raison que
 * `--header-height`, et même repli côté CSS : la hauteur d'une seule ligne.
 */
const initAdviceTitles = () => {
    const sections = Array.from(document.querySelectorAll(".block-advice"));

    if (sections.length === 0) {
        return;
    }

    const mesurer = () => {
        for (const section of sections) {
            // L'ENVELOPPE, et surtout pas le titre lui-même : son retrait haut
            // étend son aplat jusqu'au bord de la vue, et `offsetHeight`
            // compterait ces 152px — l'étiquette se rangerait alors bien plus
            // bas que le titre. L'enveloppe, elle, vaut la hauteur de FLUX,
            // que la marge négative du titre ramène à sa seule ligne.
            const tete = section.querySelector(".block-advice__head");

            if (tete === null) {
                continue;
            }

            section.style.setProperty(
                "--advice-title-height",
                `${tete.offsetHeight}px`,
            );
        }
    };

    // Le titre change de hauteur sans que la fenêtre bouge : une police qui
    // arrive, un grossissement de texte. Le seul `resize` laisserait alors un
    // calage faux.
    if (window.ResizeObserver !== undefined) {
        const observateur = new window.ResizeObserver(mesurer);

        for (const section of sections) {
            observateur.observe(section);
        }
    }

    window.addEventListener("resize", mesurer);

    // La hauteur dépend de la POLICE : mesurer avant qu'elle soit chargée fige
    // une valeur calculée sur les métriques de la police de secours, dont la
    // chasse n'est pas celle de Sligoil — un titre qui tient sur une ligne avec
    // l'une peut en demander deux avec l'autre.
    if (document.fonts !== undefined) {
        document.fonts.ready.then(mesurer);
    }

    mesurer();
};

const initHeaderHeight = () => {
    const header = document.getElementById("site-header");

    if (header === null) {
        return;
    }

    const mesurer = () => {
        document.documentElement.style.setProperty(
            "--header-height",
            `${header.offsetHeight}px`,
        );
    };

    window.addEventListener("resize", mesurer);

    // La hauteur dépend de la police : mesurer avant qu'elle soit chargée fige
    // une valeur calculée sur les métriques de la police de secours.
    if (document.fonts !== undefined) {
        document.fonts.ready.then(mesurer);
    }

    mesurer();
};

/**
 * Vidéo de fond du hero : lecture, arrêt, et préférence d'animation.
 *
 * L'attribut `autoplay` n'est PAS dans le balisage, et c'est délibéré : il
 * aurait fait jouer quelques images avant que ce script puisse l'arrêter sous
 * `prefers-reduced-motion`, or c'est précisément ce que la préférence interdit.
 * Ce script est donc le SEUL à lancer la lecture. Sans lui — JavaScript coupé,
 * script en erreur — le visiteur voit l'image d'attente, qui est la photo
 * contribuée : le hero n'est jamais vide.
 *
 * Le bouton d'arrêt suit la même règle : PHP le rend `hidden`, et il n'apparaît
 * qu'ici. Un bouton qui n'arrête rien n'est pas un bouton.
 */
const initHeroMotion = () => {
    const video = document.querySelector("[data-hero-video]");

    if (video === null) {
        return;
    }

    const hero = video.closest(".hero");
    const toggle = hero === null ? null : hero.querySelector("[data-hero-motion]");
    const label = toggle === null ? null : toggle.querySelector("[data-hero-motion-label]");

    if (hero === null || toggle === null || label === null) {
        return;
    }

    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

    const showState = (playing) => {
        hero.classList.toggle("hero--paused", ! playing);
        label.textContent = playing ? toggle.dataset.labelPause : toggle.dataset.labelPlay;
    };

    // L'état est posé AVANT d'attendre la promesse : `play()` ne se résout
    // qu'une fois la vidéo chargée, et jusque-là le bouton n'aurait aucun nom
    // accessible — relevé au harnais, le libellé restait vide.
    //
    // La promesse est REJETÉE quand le navigateur refuse la lecture — économie
    // de données, onglet ouvert en arrière-plan, règle de lecture automatique.
    // Sans ce rattrapage, la console reçoit une erreur et l'état affiché ment :
    // le bouton proposerait d'arrêter une vidéo qui ne joue pas.
    const startPlayback = () => {
        showState(true);
        video.play().catch(() => showState(false));
    };

    const applyPreference = () => {
        if (reduceMotion.matches) {
            video.pause();
            showState(false);

            return;
        }

        startPlayback();
    };

    toggle.addEventListener("click", () => {
        if (video.paused) {
            startPlayback();

            return;
        }

        video.pause();
        showState(false);
    });

    reduceMotion.addEventListener("change", applyPreference);

    toggle.hidden = false;
    applyPreference();
};

const PROTECTED_MEDIA = "img, video";

// A media inside a link keeps its menu: blocking it would also take away
// "open in a new tab" and "copy link".
const isProtectedMedia = (target) =>
    target instanceof Element && target.matches(PROTECTED_MEDIA) && target.closest("a") === null;

const initMediaProtection = () => {
    const blockOnMedia = (event) => {
        if (isProtectedMedia(event.target)) {
            event.preventDefault();
        }
    };

    document.addEventListener("contextmenu", blockOnMedia);
    document.addEventListener("dragstart", blockOnMedia);
};

document.addEventListener("DOMContentLoaded", () => {
    initMediaProtection();
    initHeaderMenu();
    initHeaderHeight();
    initAdviceTitles();
    initLogoScroll();
    initNavShape();
    initCtaShapes();
    initCarousels();
    initScrollRails();
    initAccordions();
    initVolets();
    initHeroMotion();
});
