#!/usr/bin/env bash
#
# QA du front : compilation, disponibilité des assets, puis campagne
# d'assertions jouée dans un navigateur sans interface, à deux largeurs.
#
# Prérequis : `docker compose up -d` et Google Chrome (ou Chromium) installé.
# Rien n'est écrit hors de dist/, qui n'est pas versionné.
#
# Usage : bin/qa-front.sh [--no-build]

set -uo pipefail

SITE_URL="${LCDS_SITE_URL:-http://localhost:8020}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DIST="$ROOT/website/app/themes/lcds/dist"
HARNESS_NAME="_qa-harness.html"
DRIVER_NAME="front.qa.js"
SCRATCH="$(mktemp -d)"
FAILURES=0
RUN_BUILD=1

[ "${1:-}" = "--no-build" ] && RUN_BUILD=0

cleanup() {
    rm -f "$DIST/$HARNESS_NAME" "$DIST/$DRIVER_NAME"
    rm -rf "$SCRATCH"
}
trap cleanup EXIT

CHROME=""
for candidate in \
    "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" \
    "$(command -v google-chrome-stable || true)" \
    "$(command -v google-chrome || true)" \
    "$(command -v chromium || true)"; do
    if [ -n "$candidate" ] && [ -x "$candidate" ]; then
        CHROME="$candidate"
        break
    fi
done

if [ -z "$CHROME" ]; then
    echo "QA front : aucun navigateur Chrome/Chromium trouvé." >&2
    exit 1
fi

check_url() {
    local label="$1" url="$2" code
    code="$(curl -s -o /dev/null -w '%{http_code}' "$url")"

    if [ "$code" = "200" ]; then
        printf '  PASS :: %s\n' "$label"
        return 0
    fi

    printf '  FAIL :: %s (HTTP %s)\n' "$label" "$code"
    return 1
}

# Les assets compilés gardent un nom fixe et sont servis avec un `Expires` à un
# mois : sans version dérivée du fichier, une mise en production ne parvient pas
# aux visiteurs déjà venus. On vérifie le comportement, pas la valeur.
check_asset_version() {
    local label="$1" file="$2" pattern="$3" before after essais=0

    before="$(curl -s "$SITE_URL/" | grep -o "$pattern" | head -1)"
    sleep 1
    touch "$DIST/$file"

    # Le cache `realpath` de PHP (120s par défaut) peut servir un mtime périmé
    # dans un worker Apache persistant : la nouvelle version n'apparaît pas
    # forcément à la requête suivante. On interroge jusqu'à ce qu'elle arrive,
    # au lieu de conclure trop tôt — c'était une source d'échecs intermittents.
    while [ "$essais" -lt 12 ]; do
        after="$(curl -s "$SITE_URL/" | grep -o "$pattern" | head -1)"

        if [ -n "$before" ] && [ "$after" != "$before" ]; then
            printf '  PASS :: %s (%s puis %s)\n' "$label" "$before" "$after"
            return 0
        fi

        sleep 1
        essais=$((essais + 1))
    done

    printf '  FAIL :: %s (inchangée après %ss : %s)\n' "$label" "$essais" "$before"
    return 1
}

# Chrome ne rend pas toujours la main après --dump-dom : on attend le marqueur
# de fin dans la sortie plutôt que la fin du processus.
#
# La fenêtre est fixée à 1600px et la largeur éprouvée est passée à l'iframe :
# Chrome plafonne la fenêtre à ~500px sur macOS, un --window-size=320 donnait
# une vue de 500 — voir bin/qa/harness.html.
dump_dom() {
    local width="$1" out="$SCRATCH/dump-$width.html" pid attempts=0

    # --force-prefers-reduced-motion : rend le défilement du carrousel
    # instantané, donc mesurable. Sans cela rien n'est déterministe.
    "$CHROME" --headless --disable-gpu --no-first-run --no-default-browser-check \
        --force-prefers-reduced-motion \
        --window-size=1600,900 --virtual-time-budget=8000 --dump-dom \
        --user-data-dir="$SCRATCH/profile-$width" \
        "$SITE_URL/app/themes/lcds/dist/$HARNESS_NAME?w=${width}px" > "$out" 2>/dev/null &
    pid=$!

    while [ "$attempts" -lt 30 ]; do
        if grep -q 'id="qa-results"' "$out" 2>/dev/null; then
            break
        fi
        sleep 1
        attempts=$((attempts + 1))
    done

    kill "$pid" 2>/dev/null
    wait "$pid" 2>/dev/null
    printf '%s' "$out"
}

report() {
    local width="$1" dump="$2" clean="$SCRATCH/clean-$width.txt"

    # Le dump est du HTML : on isole le bloc de résultats, on retire les balises,
    # puis on rétablit les entités — dans cet ordre, sinon un `<=` disparaît.
    sed -n '/<pre id="qa-results">/,/<\/pre>/p' "$dump" \
        | sed -e 's/<[^>]*>//g' -e 's/&lt;/</g' -e 's/&gt;/>/g' -e 's/&amp;/\&/g' \
        | grep -E '^(PASS|FAIL) ::' > "$clean" 2>/dev/null

    if [ ! -s "$clean" ]; then
        printf '  FAIL :: aucun résultat produit à %spx\n' "$width"
        return 1
    fi

    sed 's/^/  /' "$clean"
    grep -q 'FAIL ::' "$clean" && return 1
    return 0
}

# L'aperçu de l'éditeur est le SEUL rendu que voit un contributeur. Deux choses
# le conditionnent, et aucune n'est visible depuis le front : la feuille du thème
# doit être servie à l'administration, et les blocs doivent produire quelque
# chose en mode `preview`. Vérifié par WP-CLI, faute d'accès authentifié à
# wp-admin depuis un navigateur sans interface.
check_editor_preview() {
    local out

    out="$(cd "$ROOT" && docker compose exec -T php wp eval '
require_once ABSPATH . "wp-admin/includes/admin.php";

$page_id = (int) get_option("page_on_front");
$post = get_post($page_id);

if (! $post instanceof WP_Post) {
    echo "FAIL|aucune page d\x27accueil|\n";

    return;
}

$context = new WP_Block_Editor_Context(["name" => "core/edit-post", "post" => $post]);
$settings = get_block_editor_settings(get_default_block_editor_settings(), $context);
$octets = 0;

foreach ($settings["styles"] ?? [] as $style) {
    $css = (string) ($style["css"] ?? "");

    if (str_contains($css, ".hero") && str_contains($css, ".journey")) {
        $octets = strlen($css);
    }
}

printf(
    "%s|feuille du theme servie a l editeur|%d octets\n",
    $octets > 0 ? "PASS" : "FAIL",
    $octets,
);

$fuite = 0;

foreach (parse_blocks($post->post_content) as $block) {
    if (empty($block["blockName"])) {
        continue;
    }

    $nom = str_replace("acf/lcds-", "", (string) $block["blockName"]);
    $type = WP_Block_Type_Registry::get_instance()->get_registered((string) $block["blockName"]);
    $mode = $type->mode ?? null;

    // Bloc non sélectionné : l aperçu. C est ce que voit le contributeur qui
    // parcourt la page.
    $apercu = trim(acf_rendered_block($block["attrs"], "", true, $page_id));
    $indices = substr_count($apercu, "lcds-block-hint");
    $formulaire_en_apercu = str_contains($apercu, "acf-block-fields");

    // Bloc sélectionné : le mode `auto` bascule sur `edit`, et ACF rend son
    // formulaire DANS le canevas plutôt que dans la colonne de droite. C est le
    // seul emplacement qu il sait servir hors de l inspecteur — vérifié dans
    // son JS livré.
    $attrs = $block["attrs"];
    $attrs["mode"] = "edit";
    $forme = acf_rendered_block($attrs, "", true, $page_id);
    $champs = substr_count($forme, "class=\"acf-field ");

    printf(
        "%s|apercu du bloc %s|%d octets, %d indice(s) de bloc vide\n",
        $apercu !== "" && $indices === 0 && ! $formulaire_en_apercu ? "PASS" : "FAIL",
        $nom,
        strlen($apercu),
        $indices,
    );

    printf(
        "%s|formulaire du bloc %s dans le canevas|mode %s, %d champs\n",
        $mode === "auto" && str_contains($forme, "acf-block-fields") && $champs > 0 ? "PASS" : "FAIL",
        $nom,
        var_export($mode, true),
        $champs,
    );

    $fuite += substr_count(apply_filters("the_content", $post->post_content), "acf-block-fields");
}

printf(
    "%s|aucun formulaire ACF en front|%d occurrence(s)\n",
    $fuite === 0 ? "PASS" : "FAIL",
    $fuite,
);
' --allow-root 2>/dev/null | tr -d '\r')"

    if [ -z "$out" ]; then
        printf '  FAIL :: aperçu de l\x27éditeur (WP-CLI muet)\n'
        return 1
    fi

    printf '%s\n' "$out" | while IFS='|' read -r verdict label detail; do
        printf '  %s :: %s (%s)\n' "$verdict" "$label" "$detail"
    done

    printf '%s' "$out" | grep -q '^FAIL' && return 1
    return 0
}

if [ "$RUN_BUILD" -eq 1 ]; then
    echo "== Compilation du front =="
    if ! (cd "$ROOT" && docker compose run --rm node npm run build > "$SCRATCH/build.log" 2>&1); then
        echo "  FAIL :: la compilation a échoué"
        tail -20 "$SCRATCH/build.log"
        exit 1
    fi
    echo "  PASS :: npm run build"
fi

echo "== Assets servis =="
check_url "page d'accueil" "$SITE_URL/" || FAILURES=$((FAILURES + 1))
check_url "feuille de style compilée" "$SITE_URL/app/themes/lcds/dist/main.css" || FAILURES=$((FAILURES + 1))
check_url "script compilé" "$SITE_URL/app/themes/lcds/dist/main.js" || FAILURES=$((FAILURES + 1))

echo "== Invalidation du cache des assets =="
check_asset_version "version de la feuille de style" "main.css" "main\.css?ver=[0-9]*" || FAILURES=$((FAILURES + 1))
check_asset_version "version du script" "main.js" "main\.js?ver=[0-9]*" || FAILURES=$((FAILURES + 1))

cp "$ROOT/bin/qa/harness.html" "$DIST/$HARNESS_NAME"
cp "$ROOT/bin/qa/$DRIVER_NAME" "$DIST/$DRIVER_NAME"

# La navigation vient de menus amorcés par le code, pas de saisie manuelle :
# header.php appelle wp_nav_menu() avec `fallback_cb => false`, donc un menu
# vide sort un en-tête sans navigation. Les assertions d'en-tête ci-dessous
# passaient sur des entrées saisies à la main dans une base locale, invisibles
# du dépôt — exactement le trou que ce bloc ferme.
check_menus() {
    local out

    out="$(cd "$ROOT" && docker compose exec -T php wp eval '
$manquants = array();
$sans_entree = array();

foreach (LcdsMenuLocation::cases() as $location) {
    $mods = get_theme_mod("nav_menu_locations", array());
    $menu_id = (int) ($mods[$location->value] ?? 0);

    if ($menu_id === 0 || ! wp_get_nav_menu_object($menu_id)) {
        $manquants[] = $location->value;

        continue;
    }

    // Seuls les deux emplacements de l entete sont exigés non vides : aucune
    // maquette ne dessine encore le pied de page.
    $attendus = $location->items();

    if ($attendus !== array() && count((array) wp_get_nav_menu_items($menu_id)) === 0) {
        $sans_entree[] = $location->value;
    }
}

printf(
    "%s|un menu par emplacement declare|%d/%d%s\n",
    $manquants === array() ? "PASS" : "FAIL",
    count(LcdsMenuLocation::cases()) - count($manquants),
    count(LcdsMenuLocation::cases()),
    $manquants === array() ? "" : ", sans menu : " . implode(", ", $manquants),
);

printf(
    "%s|entrees en place la ou l enum en declare|%s\n",
    $sans_entree === array() ? "PASS" : "FAIL",
    $sans_entree === array() ? "aucun emplacement vide" : "vides : " . implode(", ", $sans_entree),
);
' --allow-root 2>/dev/null | tr -d '\r')"

    if [ -z "$out" ]; then
        printf '  FAIL :: navigation amorcée (WP-CLI muet)\n'
        return 1
    fi

    printf '%s\n' "$out" | while IFS='|' read -r verdict label detail; do
        printf '  %s :: %s (%s)\n' "$verdict" "$label" "$detail"
    done

    printf '%s' "$out" | grep -q '^FAIL' && return 1
    return 0
}

# Deux vérifications qui ne se voient pas depuis le navigateur.
check_a11y_serveur() {
    local echecs=0 forces titres

    # Le texte alternatif est une donnée de la médiathèque. Un composant qui
    # repasse 'alt' => '' la court-circuite et rend l'image décorative d'office
    # — constaté : les 16 images de la page d'accueil, sans exception.
    forces="$(grep -rn "'alt' => ''" "$ROOT/website/app/themes/lcds/components" \
        "$ROOT/website/app/themes/lcds/blocks" 2>/dev/null | wc -l | tr -d ' ')"

    if [ "$forces" = "0" ]; then
        printf '  PASS :: aucun composant ne force un alt vide\n'
    else
        printf '  FAIL :: %s composant(s) forcent un alt vide\n' "$forces"
        grep -rn "'alt' => ''" "$ROOT/website/app/themes/lcds/components" \
            "$ROOT/website/app/themes/lcds/blocks" 2>/dev/null | sed 's/^/    /'
        echecs=$((echecs + 1))
    fi

    # Yoast range ses gabarits de titre à l'activation. Activé sans son paquet
    # de langue, il y laisse l'anglais — « Page not found », « You searched
    # for … » et quatre libellés de fil d'Ariane sur un site déclaré en fr.
    titres="$(cd "$ROOT" && docker compose exec -T php wp eval \
        'echo count(lcds_reset_seo_titles());' --allow-root 2>/dev/null | tr -d '\r')"

    if [ "$titres" = "0" ]; then
        printf '  PASS :: gabarits de titre Yoast tous traduits\n'
    else
        printf '  FAIL :: %s gabarit(s) de titre Yoast restes en anglais\n' "${titres:-?}"
        echecs=$((echecs + 1))
    fi

    [ "$echecs" -eq 0 ] && return 0
    return 1
}

echo "== Accessibilité côté serveur =="
check_a11y_serveur || FAILURES=$((FAILURES + 1))

echo "== Navigation amorcée =="
check_menus || FAILURES=$((FAILURES + 1))

echo "== Aperçu dans l'éditeur =="
check_editor_preview || FAILURES=$((FAILURES + 1))

for width in 1440 500 320; do
    echo "== Front à ${width}px =="
    report "$width" "$(dump_dom "$width")" || FAILURES=$((FAILURES + 1))
done

echo
if [ "$FAILURES" -eq 0 ]; then
    echo "QA front : tout est au vert."
    exit 0
fi

echo "QA front : $FAILURES bloc(s) en échec."
exit 1
