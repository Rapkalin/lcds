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
            // un geste sans effet.
            carousel.classList.toggle("carousel--draggable", furthest > 1);

            // Cible atteinte : on rend la main au défilement de l'utilisateur.
            if (target !== null && Math.abs(rail.scrollLeft - target) < 2) {
                target = null;
            }
        };

        const scrollByPage = (direction) => {
            // Rail épinglé : le rapport étant de 1:1, une page de rail vaut
            // exactement une largeur de rail en défilement de page. Écrire
            // `scrollLeft` ici serait écrasé à l'image suivante par
            // l'avancement de la page.
            if (estEpingle(carousel)) {
                window.scrollBy({ top: direction * rail.clientWidth, behavior: behavior() });

                return;
            }

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

            // Sur un rail épinglé la position appartient à la page : un
            // glissement écrirait une valeur aussitôt écrasée. Les flèches
            // restent l'alternative au geste — WCAG 2.5.7.
            if (event.pointerType === "touch" || event.button !== 0 || estEpingle(carousel)) {
                return;
            }

            origine = { x: event.clientX, scroll: rail.scrollLeft };
            // Le geste prend la main sur un défilement animé en cours.
            target = null;
        });

        const relacher = () => {
            origine = null;
            carousel.classList.remove("carousel--dragging");
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
 * Journey section: vertical scroll drives a horizontal rail, one step per notch.
 *
 * Two mechanisms, and they answer two different questions.
 *
 * The first publishes ONE number — how far through the section we are — and
 * hands it to CSS, which rounds it to the nearest step. The rail transform and
 * the progress bar read that same rounded value, so they cannot fall out of
 * sync, and the rail never rests between two cards. This is the fallback path:
 * keyboard, scrollbar, touch.
 *
 * The second confiscates the WHEEL while the pinned view fills the screen: one
 * notch moves exactly one step, and everything arriving during the cadence is
 * swallowed. Pacing is therefore governed by a TIMER, not by a distance — which
 * is what makes several quick notches count as one.
 *
 * Pinning is opt-in: the `journey--pinned` class is added here and nowhere
 * else. Without JavaScript, or when the visitor asks for reduced motion, the
 * steps stay stacked, nothing is confiscated, and every word remains reachable.
 */
// Durée pendant laquelle le défilement est absorbé après une bascule d'étape.
//
// PLANCHER DUR : la transition CSS du rail, 0,45s. En dessous, l'étape suivante
// partirait avant que la précédente soit posée. Les 50ms qui restent sont la
// marge, et c'est tout ce qui séparait 600 de ce plancher.
//
// Ramenée de 600 à 500 sur retour client : l'absorption durait trop longtemps
// et il fallait attendre pour repartir. Descendre plus bas exige de raccourcir
// d'abord la transition dans block-journey.scss — les deux ne peuvent pas
// diverger.
const JOURNEY_CADENCE = 500;

// Silence au-delà duquel un évènement de molette ouvre un geste NEUF.
//
// Un geste de pavé tactile — et une roulette sous macOS — n'émet pas un
// évènement mais une TRAÎNE, qui continue près d'une seconde après que le doigt
// a quitté la surface. Tant que le flux ne s'interrompt pas, c'est encore le
// même geste : il ne peut donc pas faire avancer une seconde étape, quelle que
// soit sa durée. Une simple cadence, elle, finissait par expirer sous lui.
const JOURNEY_REPOS = 150;

// Sortie de secours, comptée depuis la dernière bascule : un défilement qui ne
// s'interrompt JAMAIS — deux doigts qui ne se lèvent pas — avance tout de même,
// sinon la section deviendrait un cul-de-sac.
//
// C'est LUI qui gouverne l'attente sur un pavé tactile, où le flux ne s'arrête
// pas et où `JOURNEY_REPOS` ne peut donc pas reconnaître de geste neuf.
// Ramené de 1600 à 1200 sur le même retour client.
//
// PLANCHER DUR : la traîne d'un geste de pavé tactile, qui dure PRÈS D'UNE
// SECONDE après que le doigt a quitté la surface. Sous 1000, cette inertie
// franchirait le plafond toute seule et un seul geste passerait deux cartes —
// le défaut que ce dispositif corrige. Il reste 200ms de marge.
const JOURNEY_PLAFOND = 1200;

const initJourneys = () => {
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

    document.querySelectorAll("[data-journey]").forEach((journey) => {
        let frame = null;

        // Le nombre de transitions, relevé UNE fois sur la propriété que le CSS
        // utilise lui aussi. Le recompter dans le DOM aurait créé une seconde
        // source, et les deux auraient fini par se contredire.
        const transitions = (Number.parseInt(
            window.getComputedStyle(journey).getPropertyValue("--journey-steps"),
            10,
        ) || 1) - 1;

        const update = () => {

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
            // La fraction BRUTE. L'arrondi au palier de l'étape est fait par le
            // CSS, qui l'applique à la translation comme au remplissage : ainsi
            // les deux lisent le même nombre arrondi, et un arrondi posé ici
            // aurait dû être refait à l'identique pour chacun.
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

        // Une étape par cran de molette, et rien d'autre pendant la bascule.
        //
        // Le geste est CONFISQUÉ tant que la section est collée et qu'il reste
        // une étape dans son sens : la page ne bouge plus d'un pixel de son
        // fait, c'est le script qui la porte d'une étape à la suivante. Deux
        // conséquences voulues :
        //
        //   - l'amplitude du geste ne compte pas, seul son SENS — une roulette
        //     lâchée d'un coup avance d'une étape, pas de quatre ;
        //   - tout ce qui arrive pendant la cadence est absorbé sans effet,
        //     donc plusieurs crans rapprochés valent un seul.
        //
        // Les deux BOUTS rendent la main, mais pas sur le geste en cours : la
        // carte de bout marque un ARRÊT, et il faut un second geste pour sortir.
        // Sans quoi on n'entrerait jamais dans la section et on n'en sortirait
        // plus.
        //
        // Et l'ARRIVÉE ne compte pas comme un cran : voir plus bas, c'est le
        // geste d'entrée qui emportait la première carte.
        //
        // Le clavier n'est pas touché, le tactile non plus, et sous mouvement
        // réduit la section n'est pas épinglée du tout : trois sorties de
        // secours si le verrou se coinçait.
        let verrou = 0;
        let plafond = 0;
        let dernier = 0;
        let etaitCollee = false;
        let arret = false;

        const auCran = (event) => {
            if (! journey.classList.contains("journey--pinned")) {
                return;
            }

            const cadre = journey.getBoundingClientRect();
            const course = journey.offsetHeight - window.innerHeight;
            const sens = Math.sign(event.deltaY);

            // `collee` : la vue épinglée occupe exactement l'écran. Hors de cet
            // intervalle, la section entre ou sort, et le défilement lui
            // appartient.
            const collee = cadre.top <= 1 && cadre.bottom >= window.innerHeight - 1;

            if (! collee) {
                etaitCollee = false;

                return;
            }

            if (course <= 0 || sens === 0 || transitions <= 0) {
                return;
            }

            const haut = cadre.top + window.scrollY;
            const avance = Math.min(1, Math.max(0, -cadre.top / course)) * transitions;
            const palier = Math.round(avance);
            const ancrer = (etape) => {
                const instant = performance.now();
                verrou = instant + JOURNEY_CADENCE;
                plafond = instant + JOURNEY_PLAFOND;
                dernier = instant;
                // Un ARRÊT est dû sur les cartes de bout : c'est de là qu'on
                // quitte la section, et il ne faut pas en partir sur la traîne
                // du geste qui vient de les poser.
                arret = etape === 0 || etape === transitions;
                // `instant` : le défilement fluide du thème étalerait le saut, et
                // la carte basculerait en cours de route. C'est la transition CSS
                // qui doit porter le glissement, pas le défilement de la page.
                window.scrollTo({
                    top: haut + (etape / transitions) * course,
                    behavior: "instant",
                });
            };

            // ARRIVÉE dans la section : on s'ancre sur le BORD franchi — la
            // première étape si l'on descend, la dernière si l'on remonte — et
            // ce geste-ci ne compte pas comme un cran.
            //
            // Le bord, et non le palier atteint. Le geste d'entrée n'était pas
            // confisqué, la section n'étant pas encore collée ; et Chrome ANIME
            // le défilement de molette, donc la page continue de glisser après
            // le dernier évènement, sans qu'aucun ne soit là pour l'arrêter.
            // Elle dépassait ainsi la première carte, et s'ancrer « au plus
            // proche » entérinait le dépassement.
            //
            // La reconnaissance est bornée à UNE étape du bord : au-delà, on
            // n'arrive pas, on est déjà dedans — un visiteur amené là au clavier
            // ou à la barre de défilement ne doit pas être ramené en arrière de
            // plusieurs écrans à son premier coup de molette.
            if (! etaitCollee) {
                etaitCollee = true;

                const bord = sens > 0 ? 0 : transitions;

                if (Math.abs(avance - bord) <= 1) {
                    event.preventDefault();
                    // `ancrer` pose l'arrêt : le bord EST une carte de bout,
                    // donc l'arrivée et la sortie se tiennent par la même règle.
                    ancrer(bord);

                    return;
                }
            }

            const cible = palier + sens;
            const maintenant = performance.now();
            // Un geste NEUF se reconnaît à un SILENCE qui le précède. Tant que
            // les évènements se suivent, c'est le même geste qui vit sur son
            // inertie, et il a déjà eu son étape — c'est ce qui rend le
            // blocage catégorique plutôt que seulement probable. Le plafond
            // garde la sortie : un flux qui ne s'interrompt jamais avance tout
            // de même.
            const gesteNeuf = maintenant - dernier > JOURNEY_REPOS
                || maintenant >= plafond;
            const sortie = cible < 0 || cible > transitions;

            dernier = maintenant;

            // L'ARRÊT sur la carte de bout. Elle est posée, et c'est de là qu'on
            // sort : sans cet arrêt, la traîne du geste qui vient de la poser
            // emportait la page hors de la section et la carte n'apparaissait
            // qu'un instant. Il faut donc un geste NEUF pour sortir, comme il en
            // faut un pour avancer.
            //
            // `arret` retombe dès que la sortie est accordée : les évènements
            // suivants du même geste ne doivent pas rattraper la page en route.
            if (sortie && ! arret) {
                return;
            }

            if (maintenant < verrou || ! gesteNeuf) {
                event.preventDefault();

                return;
            }

            if (sortie) {
                arret = false;

                return;
            }

            event.preventDefault();
            ancrer(cible);
        };

        apply();
        reduceMotion.addEventListener("change", apply);
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
        window.addEventListener("resize", schedule);
        // `passive: false` explicitement : Chrome rend passif tout écouteur de
        // `wheel` posé sur la fenêtre, et `preventDefault` y serait sans effet.
        window.addEventListener("wheel", auCran, { passive: false });
    });
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

document.addEventListener("DOMContentLoaded", () => {
    initHeaderMenu();
    initHeaderHeight();
    initNavShape();
    initCtaShapes();
    initCarousels();
    initScrollRails();
    initAccordions();
    initJourneys();
});
