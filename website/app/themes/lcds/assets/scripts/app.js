// /!\ CAUTION /!\
// This loads the css even though it is not use here do not remove it
import css from "../styles/app.scss"
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

/**
 * Horizontal carousels.
 *
 * Scrolling itself is native — touch, trackpad, horizontal wheel and keyboard
 * all work without this file. What follows only adds the indicator and the two
 * buttons on top of it, so a JavaScript failure degrades to a plain scroller.
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

            // Cible atteinte : on rend la main au défilement de l'utilisateur.
            if (target !== null && Math.abs(rail.scrollLeft - target) < 2) {
                target = null;
            }
        };

        const scrollByPage = (direction) => {
            const furthest = rail.scrollWidth - rail.clientWidth;
            const from = target === null ? rail.scrollLeft : target;
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
const initAccordions = () => {
    document.querySelectorAll("[data-disclosure]").forEach((trigger) => {
        const panel = document.getElementById(trigger.getAttribute("aria-controls"));

        if (panel === null) {
            return;
        }

        trigger.addEventListener("click", () => {
            const isOpen = trigger.getAttribute("aria-expanded") === "true";
            trigger.setAttribute("aria-expanded", String(!isOpen));
            panel.hidden = isOpen;
            // La carte de technologie masque son titre quand le panneau est
            // ouvert : l'état vit sur la carte, pas sur le bouton.
            trigger.closest(".tech-card")?.classList.toggle("tech-card--open", !isOpen);
        });
    });
};

/**
 * Journey section: vertical scroll drives a horizontal rail.
 *
 * The script only ever computes one number — how far through the section we
 * are — and hands it to CSS. The rail transform and the progress bar both read
 * that same variable, so they cannot fall out of sync.
 *
 * Pinning is opt-in: the `journey--pinned` class is added here and nowhere
 * else. Without JavaScript, or when the visitor asks for reduced motion, the
 * steps stay stacked and every word remains reachable.
 */
const initJourneys = () => {
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

    document.querySelectorAll("[data-journey]").forEach((journey) => {
        let frame = null;

        const update = () => {
            frame = null;

            if (!journey.classList.contains("journey--pinned")) {
                return;
            }

            // La course utile : tout ce qui dépasse de la hauteur de la vue.
            const course = journey.offsetHeight - window.innerHeight;

            if (course <= 0) {
                journey.style.setProperty("--journey-progress", "0");

                return;
            }

            const travelled = -journey.getBoundingClientRect().top;
            const progress = Math.min(1, Math.max(0, travelled / course));
            journey.style.setProperty("--journey-progress", String(progress));
        };

        // Le défilement peut émettre bien plus souvent que le navigateur ne
        // peint : on ne recalcule qu'une fois par image.
        const schedule = () => {
            if (frame === null) {
                frame = window.requestAnimationFrame(update);
            }
        };

        const apply = () => {
            if (reduceMotion.matches) {
                journey.classList.remove("journey--pinned");
                journey.style.removeProperty("--journey-progress");

                return;
            }

            journey.classList.add("journey--pinned");
            update();
        };

        apply();
        reduceMotion.addEventListener("change", apply);
        window.addEventListener("scroll", schedule, { passive: true });
        window.addEventListener("resize", schedule);
    });
};

/**
 * Footer reveal.
 *
 * The panel covers a full-bleed visual; as the page bottoms out, it lifts and
 * uncovers it. Same discipline as the journey section: this file computes ONE
 * number — how far through the reveal we are — and hands it to CSS, which owns
 * every pixel.
 *
 * Opt-in, exactly like `journey--pinned`: without JavaScript, or when the
 * visitor asks for reduced motion, the panel stays put and the visual is simply
 * visible beneath it. That is the mockup, and nothing becomes unreachable.
 */
const initFooterReveal = () => {
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

    document.querySelectorAll("[data-footer-reveal]").forEach((wrapper) => {
        let frame = null;

        const update = () => {
            frame = null;

            if (!wrapper.classList.contains("footer-reveal--animated")) {
                return;
            }

            // La hauteur découverte se lit sur la RÉSERVE du bloc, et non sur
            // la variable CSS ni sur le visuel : une propriété personnalisée
            // n'est pas résolue en pixels — `32.0625rem` donnait 32 après
            // parseFloat — et le visuel est volontairement plus haut, puisqu'il
            // remonte sous les coins arrondis du panneau.
            const reveal = parseFloat(window.getComputedStyle(wrapper).paddingBottom);

            if (!(reveal > 0)) {
                return;
            }

            // 0 quand le bas du bloc est encore à une hauteur de visuel sous la
            // vue, 1 quand il l'atteint.
            const remaining = wrapper.getBoundingClientRect().bottom - window.innerHeight;
            const progress = Math.min(1, Math.max(0, 1 - remaining / reveal));
            wrapper.style.setProperty("--reveal-progress", String(progress));
        };

        const schedule = () => {
            if (frame === null) {
                frame = window.requestAnimationFrame(update);
            }
        };

        const apply = () => {
            if (reduceMotion.matches) {
                wrapper.classList.remove("footer-reveal--animated");
                wrapper.style.removeProperty("--reveal-progress");

                return;
            }

            wrapper.classList.add("footer-reveal--animated");
            update();
        };

        apply();
        reduceMotion.addEventListener("change", apply);
        window.addEventListener("scroll", schedule, { passive: true });
        window.addEventListener("resize", schedule);
    });
};

/* ------------------------------------------------------------------------- *
 * La forme de la barre de navigation.
 *
 * Les pastilles blanches et les collets qui les relient sont tracés d'un seul
 * `<path>` peint derrière les liens. C'est ce qui donne la CONTINUITÉ : un
 * chaînon posé dans l'écart resterait un second fond, et deux fonds qui se
 * touchent laissent toujours une couture. Ici il n'y a qu'une silhouette.
 *
 * Relevé sur la référence (floema.com), sur une rangée de 36,80 de haut pour un
 * arrondi de 12,51 : le collet s'accroche à 19,4° sur l'arrondi — donc il en
 * mange la plus grande part — et ses arêtes repartent tangentiellement, sans
 * angle. C'est cet angle, et non une largeur de chaînon, qui fixe la taille de
 * guêpe : elle vaut ici 63 % de la hauteur de la rangée.
 * ------------------------------------------------------------------------- */

const NAV_ANGLE_ACCROCHE = (19.4 * Math.PI) / 180;

// Longueur des poignées de Bézier, en fraction de l'arrondi. Elle vaut la
// moitié de l'écart tant que celui-ci est petit — c'est le rapport relevé sur
// la référence — puis PLAFONNE. Sans ce plafond, un écart de 20px enverrait les
// poignées si loin que les deux arêtes se croiseraient et fermeraient le collet.
const NAV_POIGNEE_MAX = 0.5;

const cheminBarre = (boites, rayon, hauteur) => {
    const dx = rayon * (1 - Math.cos(NAV_ANGLE_ACCROCHE));
    const dy = rayon * (1 - Math.sin(NAV_ANGLE_ACCROCHE));
    // Tangente unitaire à l'arrondi au point d'accroche.
    const tx = Math.sin(NAV_ANGLE_ACCROCHE);
    const ty = Math.cos(NAV_ANGLE_ACCROCHE);
    const dernier = boites.length - 1;
    const arc = (x, y) => `A${rayon} ${rayon} 0 0 1 ${x} ${y}`;
    const poignee = (ecart) => Math.min(ecart / 2, rayon * NAV_POIGNEE_MAX);
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

const initNavShape = () => {
    const nav = document.querySelector(".site-nav");

    if (nav === null) {
        return;
    }

    const svg = nav.querySelector(".site-nav__shape");
    const trace = svg === null ? null : svg.querySelector("path");
    const liste = nav.querySelector(".site-nav__list");

    if (trace === null || liste === null) {
        return;
    }

    // Sous le point de rupture la navigation devient un panneau vertical : les
    // pastilles ne forment plus une rangée, il n'y a plus de barre à tracer.
    const rangee = window.matchMedia("(min-width: 1025px)");

    const update = () => {
        const liens = Array.from(liste.querySelectorAll("a"));

        if (liens.length === 0 || ! rangee.matches) {
            nav.classList.remove("site-nav--shaped");

            return;
        }

        const cadre = nav.getBoundingClientRect();
        const boites = liens.map((lien) => {
            const boite = lien.getBoundingClientRect();

            return {
                gauche: boite.left - cadre.left,
                droite: boite.right - cadre.left,
                haut: boite.top - cadre.top,
                hauteur: boite.height,
            };
        });

        // Une rangée, et une seule : à 200 % de taille de texte les pastilles
        // passent à la ligne, et un tracé qui suppose une bande unique peindrait
        // alors un bloc plein en travers du menu.
        const hauteur = boites[0].hauteur;
        const alignees = boites.every(
            (boite) => Math.abs(boite.haut) < 0.5 && Math.abs(boite.hauteur - hauteur) < 0.5,
        );

        if (! alignees || hauteur <= 0) {
            nav.classList.remove("site-nav--shaped");

            return;
        }

        const rayon = parseFloat(window.getComputedStyle(liens[0]).borderTopLeftRadius) || 0;

        svg.setAttribute("viewBox", `0 0 ${cadre.width} ${hauteur}`);
        trace.setAttribute("d", cheminBarre(boites, rayon, hauteur));
        nav.classList.add("site-nav--shaped");
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

    liste.addEventListener("transitionstart", (event) => {
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
    rangee.addEventListener("change", update);

    // La largeur des pastilles dépend de la police : tracer avant qu'elle soit
    // chargée fige la forme sur les métriques de la police de secours.
    if (document.fonts !== undefined) {
        document.fonts.ready.then(update);
    }

    update();
};

document.addEventListener("DOMContentLoaded", () => {
    initHeaderMenu();
    initNavShape();
    initCarousels();
    initAccordions();
    initJourneys();
    initFooterReveal();
});
