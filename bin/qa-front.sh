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

# La contribution de la page d'accueil passe par UN champ de contenu flexible,
# et l'éditeur de blocs y est coupé. Rien de tout ça ne se voit depuis le front.
#
# Les assertions précédentes bouclaient sur `parse_blocks(post_content)` : après
# la bascule, ce contenu est vide, la boucle ne tournait plus et le bloc
# n'émettait PLUS AUCUNE assertion — sans échouer. D'où la vérification
# explicite du nombre attendu ci-dessous.
check_contribution() {
    local out

    out="$(cd "$ROOT" && docker compose exec -T php wp eval '
$theme = get_template_directory();
$fields = function_exists("acf_get_fields") ? (array) acf_get_fields("group_lcds_homepage") : array();
$flexible = null;

foreach ($fields as $field) {
    if (($field["name"] ?? "") === "sections") {
        $flexible = $field;
    }
}

if ($flexible === null) {
    echo "FAIL|groupe de champs de la page d accueil|introuvable\n";

    return;
}

$declares = array_values(array_map(static fn($l) => (string) $l["name"], (array) $flexible["layouts"]));
$gabarits = array_map(static fn($p) => basename((string) $p, ".php"), (array) glob($theme . "/layouts/*.php"));

sort($declares);
sort($gabarits);

printf(
    "%s|un gabarit par layout declare|%d layouts, %d gabarits%s\n",
    $declares === $gabarits ? "PASS" : "FAIL",
    count($declares),
    count($gabarits),
    $declares === $gabarits ? "" : " — ecart : " . implode(", ", array_merge(
        array_diff($declares, $gabarits),
        array_diff($gabarits, $declares),
    )),
);

// Un catalogue vide passerait la comparaison ci-dessus : on exige le compte.
printf(
    "%s|le catalogue porte les six sections|%s\n",
    count($declares) === 6 ? "PASS" : "FAIL",
    implode(", ", $declares),
);

$page_id = (int) get_option("page_on_front");
$rangees = get_post_meta($page_id, "sections", true);
$rangees = is_array($rangees) ? $rangees : array();

printf(
    "%s|la page d accueil porte des sections|%d rangee(s) : %s\n",
    $rangees !== array() ? "PASS" : "FAIL",
    count($rangees),
    implode(" > ", $rangees),
);

printf(
    "%s|aucun balisage de bloc residuel dans post_content|%d octets\n",
    strlen((string) get_post($page_id)->post_content) === 0 ? "PASS" : "FAIL",
    strlen((string) get_post($page_id)->post_content),
);

// Coupé sur la page contribuée par ACF, actif ailleurs : élargir le filtre à
// tout le site doit rester une décision, pas un effet de bord.
$autre = get_posts(array("post_type" => "page", "exclude" => array($page_id), "numberposts" => 1, "post_status" => "any"));
printf(
    "%s|editeur de blocs coupe sur la page d accueil, actif ailleurs|%s / %s\n",
    ! use_block_editor_for_post(get_post($page_id)) && ($autre === array() || use_block_editor_for_post($autre[0])) ? "PASS" : "FAIL",
    use_block_editor_for_post(get_post($page_id)) ? "actif" : "coupe",
    $autre === array() ? "aucune autre page" : (use_block_editor_for_post($autre[0]) ? "actif" : "coupe"),
);
' --allow-root 2>/dev/null | tr -d '\r')"

    local attendues=5
    local obtenues

    obtenues="$(printf '%s\n' "$out" | grep -cE '^(PASS|FAIL)\|')"

    if [ "$obtenues" != "$attendues" ]; then
        printf '  FAIL :: contribution (%s assertion(s) sur %s — le bloc ne teste plus ce qu%s il prétend)\n' \
            "$obtenues" "$attendues" "'"
        printf '%s\n' "$out" | sed 's/^/    /'
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

# Le pied de page lit composer.json : une désynchronisation est impossible par
# construction, il n'y a qu'une source. Ce qui est vérifié ici, c'est que le
# fichier est ATTEIGNABLE — il vit hors du docroot et n'est embarqué dans
# l'artefact de déploiement que par une ligne explicite. Oubliée, le pied de
# page perd sa version en silence. Éprouvé en rendant le fichier illisible.
# Voir la règle 8 de CLAUDE.md.
check_version() {
    local declaree affichee

    declaree="$(python3 -c "import json;print(json.load(open('$ROOT/composer.json')).get('version',''))" 2>/dev/null)"
    affichee="$(curl -s "$SITE_URL/" | grep -o 'Version [0-9][0-9A-Za-z.+-]*' | head -1 | sed 's/^Version //')"

    if [ -n "$declaree" ] && [ "$declaree" = "$affichee" ]; then
        printf '  PASS :: version du pied de page = celle de composer.json (%s)\n' "$declaree"
        return 0
    fi

    printf '  FAIL :: version du pied de page (%s) != composer.json (%s)\n' "${affichee:-absente}" "${declaree:-absente}"
    return 1
}

echo "== Version du site =="
check_version || FAILURES=$((FAILURES + 1))

echo "== Contribution de la page d'accueil =="
check_contribution || FAILURES=$((FAILURES + 1))

echo "== Accessibilité côté serveur =="
check_a11y_serveur || FAILURES=$((FAILURES + 1))

echo "== Navigation amorcée =="
check_menus || FAILURES=$((FAILURES + 1))

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
