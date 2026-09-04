// /!\ CAUTION /!\
// This loads the css even though it is not use here do not remove it
import css from "../styles/app.scss"
// /!\ END OF CAUTION /!\

/**
 * Mobile header menu.
 *
 * The open state lives in a single place — the `is-menu-open` class on <body> —
 * which both the scroll lock and the panel visibility read from.
 */
const initHeaderMenu = () => {
    const toggle = document.querySelector(".site-header__toggle");
    const panel = document.getElementById("site-header-nav");

    if (toggle === null || panel === null) {
        return;
    }

    const setOpen = (isOpen) => {
        toggle.setAttribute("aria-expanded", String(isOpen));
        document.body.classList.toggle("is-menu-open", isOpen);
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
 * Disclosure panels (treatments accordion).
 *
 * Several panels may be open at once. The mockup shows a single one open, but
 * that reads as a demonstration of the open state rather than a rule — and
 * closing a panel the visitor did not ask to close is worse than a long page.
 */
const initAccordions = () => {
    document.querySelectorAll(".accordion__trigger").forEach((trigger) => {
        const panel = document.getElementById(trigger.getAttribute("aria-controls"));

        if (panel === null) {
            return;
        }

        trigger.addEventListener("click", () => {
            const isOpen = trigger.getAttribute("aria-expanded") === "true";
            trigger.setAttribute("aria-expanded", String(!isOpen));
            panel.hidden = isOpen;
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

document.addEventListener("DOMContentLoaded", () => {
    initHeaderMenu();
    initCarousels();
    initAccordions();
    initJourneys();
});
