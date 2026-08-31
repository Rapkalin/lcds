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

document.addEventListener("DOMContentLoaded", () => {
    initHeaderMenu();
});
