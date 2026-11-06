#!/usr/bin/env bash
#
# QA du front : compilation, disponibilité des assets, puis campagne
# d'assertions jouée dans un navigateur sans interface, à deux largeurs.
#
# Prérequis : `docker compose up -d`, Google Chrome (ou Chromium) et Node 22 ou
# plus sur l'hôte (pilote de la campagne CSP).
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
CSP_SEEDED=0

[ "${1:-}" = "--no-build" ] && RUN_BUILD=0

cleanup() {
    [ "$CSP_SEEDED" -eq 1 ] && csp_seed restore > /dev/null
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
    local width="$1" page="${2:-/}" etiquette out pid attempts=0

    etiquette="$(printf '%s' "$width$page" | tr -c '[:alnum:]' '-')"
    out="$SCRATCH/dump-$etiquette.html"

    # --force-prefers-reduced-motion : rend le défilement du carrousel
    # instantané, donc mesurable. Sans cela rien n'est déterministe.
    #
    # --virtual-time-budget compte le temps VIRTUEL, que chaque `setTimeout` de
    # la campagne avance d'un coup. Les épreuves du verrou de molette en
    # consomment une dizaine de secondes à elles seules ; à 8000 la campagne
    # n'atteignait plus sa fin et le pilote ne trouvait aucun résultat. Le
    # relever ne coûte pas de temps réel, ce budget n'étant pas une attente.
    "$CHROME" --headless --disable-gpu --no-first-run --no-default-browser-check \
        --force-prefers-reduced-motion \
        --window-size=1600,900 --virtual-time-budget=60000 --dump-dom \
        --user-data-dir="$SCRATCH/profile-$etiquette" \
        "$SITE_URL/app/themes/lcds/dist/$HARNESS_NAME?w=${width}px&p=${page}" > "$out" 2>/dev/null &
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
    local width="$1" dump="$2" clean="$SCRATCH/clean-$(basename "$2").txt"

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

// Un catalogue vide passerait la comparaison ci-dessus : on exige qu il ne le
// soit pas. Le NOMBRE, lui, n est plus verrouille : il a change a chaque
// section ajoutee, un compte ecrit en dur rougissait a chaque livraison sans
// rien apprendre que la comparaison ci-dessus ne dise deja.
printf(
    "%s|le catalogue n est pas vide|%d sections : %s\n",
    $declares !== array() ? "PASS" : "FAIL",
    count($declares),
    implode(", ", $declares),
);

// La configuration des champs doit venir du FICHIER, pas de la base : c est
// tout l interet de la bascule. Enregistrer un groupe depuis l interface d ACF
// cree une copie en base, et cette copie prend la main si le JSON manque ou
// arrive perime — le deploiement rejouerait alors une configuration fantome
// que personne ne peut relire en diff.
$servi = acf_get_field_group("group_lcds_homepage");
$fichiers = function_exists("acf_get_local_json_files") ? acf_get_local_json_files() : array();

printf(
    "%s|configuration des champs servie depuis un fichier|ID=%s local=%s\n",
    (int) ($servi["ID"] ?? -1) === 0 && ($servi["local"] ?? "") === "json" ? "PASS" : "FAIL",
    var_export($servi["ID"] ?? null, true),
    var_export($servi["local"] ?? null, true),
);

printf(
    "%s|le JSON du groupe est bien present|%s\n",
    isset($fichiers["group_lcds_homepage"]) ? "PASS" : "FAIL",
    $fichiers["group_lcds_homepage"] ?? "ABSENT",
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

    local attendues=7
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

# La conversion WebP est de l'INFRASTRUCTURE, et elle tombe en SILENCE : sans
# support WebP dans la bibliothèque d'images, WordPress retombe sur du JPEG sans
# rien signaler — ni erreur, ni avertissement, ni trace. Le Dockerfile porte
# déjà l'avertissement sur `--with-webp` ; ceci le rend mesurable.
#
# Les filtres sont lus, mais surtout ÉPROUVÉS : un encodage réel est demandé à
# la bibliothèque, sur une image fabriquée pour l'occasion et retirée ensuite.
# Lire la table de correspondance ne prouve rien de la capacité à encoder.
check_webp() {
    local out

    out="$(cd "$ROOT" && docker compose exec -T php wp eval '
printf(
    "%s|le module WebP est charge|%s\n",
    defined("LCDS_WEBP_QUALITY") && defined("LCDS_WEBP_SOURCE_FORMATS") ? "PASS" : "FAIL",
    defined("LCDS_WEBP_QUALITY") ? "qualite " . LCDS_WEBP_QUALITY : "CONSTANTES ABSENTES",
);

if (! defined("LCDS_WEBP_SOURCE_FORMATS")) {
    return;
}

$formats = apply_filters("image_editor_output_format", array());
$non_mappes = array();

foreach (LCDS_WEBP_SOURCE_FORMATS as $mime) {
    if (($formats[$mime] ?? "") !== "image/webp") {
        $non_mappes[] = $mime;
    }
}

printf(
    "%s|chaque format source declare sort en WebP|%d format(s)%s\n",
    $non_mappes === array() ? "PASS" : "FAIL",
    count(LCDS_WEBP_SOURCE_FORMATS),
    $non_mappes === array() ? " : " . implode(", ", LCDS_WEBP_SOURCE_FORMATS) : " — NON MAPPES : " . implode(", ", $non_mappes),
);

// L exclusion compte autant que l inclusion : le vectoriel et l anime ne se
// pretent pas a ce traitement, et les convertir casserait l animation des GIF
// comme le redimensionnement libre des SVG.
$convertis_a_tort = array();

foreach (array("image/gif", "image/svg+xml") as $mime) {
    if (isset($formats[$mime])) {
        $convertis_a_tort[] = $mime . " -> " . $formats[$mime];
    }
}

printf(
    "%s|le GIF et le SVG restent hors du traitement|%s\n",
    $convertis_a_tort === array() ? "PASS" : "FAIL",
    $convertis_a_tort === array() ? "aucun des deux mappe" : "CONVERTIS : " . implode(", ", $convertis_a_tort),
);

// La qualite doit viser le WebP SEUL : appliquee a tous les formats, elle
// recompresserait aussi les JPEG servis par ailleurs.
$sur_webp = (int) apply_filters("wp_editor_set_quality", 90, "image/webp");
$sur_jpeg = (int) apply_filters("wp_editor_set_quality", 90, "image/jpeg");

printf(
    "%s|la qualite ne vise que le WebP|webp %d, jpeg %d\n",
    $sur_webp === LCDS_WEBP_QUALITY && $sur_jpeg === 90 ? "PASS" : "FAIL",
    $sur_webp,
    $sur_jpeg,
);

/*
 * L EPREUVE REELLE : une image est fabriquee, redimensionnee et enregistree par
 * le meme chemin que celui d un televersement. Ce qui est verifie, c est le
 * fichier PRODUIT — son type MIME, son extension, et le fait qu il soit
 * reellement decodable comme du WebP.
 *
 * Sans support WebP dans la bibliotheque, WordPress retombe ici sur du JPEG
 * sans rien dire. C est la seule assertion qui s en apercoit.
 */
$dossier = get_temp_dir();
$source = $dossier . "lcds-qa-webp-source.jpg";
$toile = imagecreatetruecolor(120, 90);
imagefilledrectangle($toile, 0, 0, 119, 89, imagecolorallocate($toile, 200, 80, 20));
imagejpeg($toile, $source);
imagedestroy($toile);

$editeur = wp_get_image_editor($source);

if (is_wp_error($editeur)) {
    printf("FAIL|une sous-taille est reellement encodee en WebP|aucun editeur : %s\n", $editeur->get_error_message());
    @unlink($source);

    return;
}

$editeur->resize(60, 45, true);
$ecrit = $editeur->save();
$produit = is_wp_error($ecrit) ? null : ($ecrit["path"] ?? "");
$signature = $produit !== null && is_readable($produit) ? file_get_contents($produit, false, null, 0, 12) : "";
// Un fichier WebP commence par « RIFF », taille sur 4 octets, puis « WEBP ».
$est_webp = strlen($signature) === 12 && substr($signature, 0, 4) === "RIFF" && substr($signature, 8, 4) === "WEBP";

printf(
    "%s|une sous-taille est reellement encodee en WebP|%s, %s, entete %s\n",
    $est_webp && ! is_wp_error($ecrit) && ($ecrit["mime-type"] ?? "") === "image/webp" ? "PASS" : "FAIL",
    is_wp_error($ecrit) ? "ECHEC : " . $ecrit->get_error_message() : ($ecrit["mime-type"] ?? "sans type"),
    is_wp_error($ecrit) ? "-" : ($ecrit["file"] ?? "sans nom"),
    $est_webp ? "RIFF/WEBP" : "NON RECONNU",
);

// La campagne ne laisse rien derriere elle.
@unlink($source);

if ($produit !== null && $produit !== "") {
    @unlink($produit);
}
' --allow-root 2>/dev/null | tr -d '\r')"

    # Le compte est VERROUILLÉ : un `return` anticipé du bloc PHP ci-dessus — la
    # constante absente, l'éditeur introuvable — n'émet qu'une partie des
    # assertions. Sans ce garde-fou, le bloc se tairait au lieu d'échouer.
    local attendues=5
    local obtenues

    obtenues="$(printf '%s\n' "$out" | grep -cE '^(PASS|FAIL)\|')"

    if [ "$obtenues" != "$attendues" ]; then
        printf '  FAIL :: conversion WebP (%s assertion(s) sur %s — le bloc ne teste plus ce qu%s il prétend)\n' \
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

# Le rôle de contribution : ce qu'il PEUT et surtout ce qu'il ne peut pas. Les
# capacités absentes comptent plus que les présentes — `manage_options` ouvre
# les sept écrans de Réglages du cœur et l'éditeur brut des options en base.
check_role() {
    local out

    out="$(cd "$ROOT" && docker compose exec -T php wp eval '
$role = get_role("lcds_contributeur");

if (! $role instanceof WP_Role) {
    echo "FAIL|role de contribution|absent\n";

    return;
}

$attendues = array("edit_pages", "publish_pages", "upload_files", "edit_theme_options", "lcds_manage_settings", "read");
$interdites = array("manage_options", "activate_plugins", "install_plugins", "switch_themes", "edit_themes", "list_users", "edit_users", "delete_users", "update_core", "export", "import");

$manquantes = array();
$deTrop = array();

foreach ($attendues as $cap) {
    if (empty($role->capabilities[$cap])) {
        $manquantes[] = $cap;
    }
}

foreach ($interdites as $cap) {
    if (! empty($role->capabilities[$cap])) {
        $deTrop[] = $cap;
    }
}

printf(
    "%s|le role porte ce qu il lui faut|%d capacites%s\n",
    $manquantes === array() ? "PASS" : "FAIL",
    count(array_filter($role->capabilities)),
    $manquantes === array() ? "" : " — manque " . implode(", ", $manquantes),
);

printf(
    "%s|le role ne porte AUCUNE capacite d administration|%s\n",
    $deTrop === array() ? "PASS" : "FAIL",
    $deTrop === array() ? "verifie sur " . count($interdites) . " capacites" : "DE TROP : " . implode(", ", $deTrop),
);

printf(
    "%s|l administrateur garde l acces a la configuration|%s\n",
    ! empty(get_role("administrator")->capabilities["lcds_manage_settings"]) ? "PASS" : "FAIL",
    ! empty(get_role("administrator")->capabilities["lcds_manage_settings"]) ? "oui" : "non",
);

global $wp_roles;
$attribuables = array_keys(apply_filters("editable_roles", $wp_roles->roles));
sort($attribuables);
$attendus = array("administrator", "lcds_contributeur");

printf(
    "%s|seuls deux roles sont attribuables|%s\n",
    $attribuables === $attendus ? "PASS" : "FAIL",
    implode(", ", $attribuables) . " (sur " . count($wp_roles->roles) . " declares)",
);

// Un compte portant un role hors des deux doit garder le sien : sans son
// option dans la liste, l enregistrement lui en attribuerait une autre.
$temoin = wp_insert_user(array("user_login" => "lcds_qa_temoin", "user_pass" => wp_generate_password(), "role" => "editor"));

if (is_wp_error($temoin)) {
    printf("FAIL|le role d un compte edite survit|creation du temoin impossible\n");
} else {
    $_REQUEST["user_id"] = $temoin;
    $edite = array_keys(apply_filters("editable_roles", $wp_roles->roles));
    unset($_REQUEST["user_id"]);
    require_once ABSPATH . "wp-admin/includes/user.php";
    wp_delete_user($temoin);

    printf(
        "%s|le role d un compte edite survit dans la liste|%s\n",
        in_array("editor", $edite, true) ? "PASS" : "FAIL",
        implode(", ", $edite),
    );
}

// La feuille masque bien ce qu il faut, et RIEN de ce qui reste.
ob_start();
lcds_profile_styles();
$css = (string) ob_get_clean();
$oublies = array();
$sacrifies = array();

foreach (LcdsProfileField::hiddenRows() as $classe) {
    if (! str_contains($css, "." . $classe)) {
        $oublies[] = $classe;
    }
}

foreach (LcdsProfileField::keptRows() as $classe) {
    if (str_contains($css, "." . $classe)) {
        $sacrifies[] = $classe;
    }
}

printf(
    "%s|la feuille masque les %d lignes hors perimetre|%s\n",
    $oublies === array() ? "PASS" : "FAIL",
    count(LcdsProfileField::hiddenRows()),
    $oublies === array() ? "aucune oubliee" : "OUBLIEES : " . implode(", ", $oublies),
);

printf(
    "%s|la feuille ne touche AUCUN champ garde|%s\n",
    $sacrifies === array() ? "PASS" : "FAIL",
    $sacrifies === array() ? "verifie sur " . count(LcdsProfileField::keptRows()) . " champs" : "MASQUES : " . implode(", ", $sacrifies),
);

// Les retraits cote SERVEUR, eux, ne sont pas de la mise en forme : la
// section n est pas rendue du tout.
$contrib = wp_insert_user(array("user_login" => "lcds_qa_contrib", "user_pass" => wp_generate_password(), "role" => "lcds_contributeur"));

if (is_wp_error($contrib)) {
    printf("FAIL|les sections hors perimetre sont retirees cote serveur|creation du temoin impossible\n");
} else {
    $avant = get_current_user_id();
    wp_set_current_user($contrib);
    lcds_trim_profile();
    wp_set_current_user($avant);
    require_once ABSPATH . "wp-admin/includes/user.php";
    wp_delete_user($contrib);

    $retires = array(
        "capacites supplementaires" => false === apply_filters("additional_capabilities_display", true, null),
        "mots de passe d application" => false === apply_filters("wp_is_application_passwords_available_for_user", true, null),
        "moyens de contact" => array() === apply_filters("user_contactmethods", array("x" => "y")),
    );
    $restants = array_keys(array_filter($retires, static fn ($ok) => ! $ok));

    printf(
        "%s|les 3 sections hors perimetre sont retirees cote serveur|%s\n",
        $restants === array() ? "PASS" : "FAIL",
        $restants === array() ? "aucune rendue" : "ENCORE RENDUES : " . implode(", ", $restants),
    );
}

// `:has()` doit vivre dans sa PROPRE regle : un navigateur qui l ignore jette
// la liste entiere de selecteurs, y compris les lignes ordinaires.
$avant = substr($css, 0, strpos($css, ":has("));

printf(
    "%s|les selecteurs :has() sont isoles dans leur regle|%s\n",
    str_contains($avant, "display: none") ? "PASS" : "FAIL",
    str_contains($avant, "display: none") ? "oui" : "NON : une regle unique tomberait entierement",
);

// La boite de Yoast doit passer APRES celles de la contribution.
printf(
    "%s|boite SEO sous les blocs de contribution|priorite %s\n",
    apply_filters("wpseo_metabox_prio", "high") === "low" ? "PASS" : "FAIL",
    apply_filters("wpseo_metabox_prio", "high"),
);
' --allow-root 2>/dev/null | tr -d '\r')"

    if [ -z "$out" ]; then
        printf '  FAIL :: rôle de contribution (WP-CLI muet)\n'
        return 1
    fi

    printf '%s\n' "$out" | while IFS='|' read -r verdict label detail; do
        printf '  %s :: %s (%s)\n' "$verdict" "$label" "$detail"
    done

    printf '%s' "$out" | grep -q '^FAIL' && return 1
    return 0
}

# Le journal des connexions vient du module rapkalin-security (Composer) : ses
# propres tests vivent dans son depot. On ne verifie ici que son integration a
# LCDS — ecran reserve aux administrateurs, connexion inscrite sans adresse IP,
# erreurs PHP captees malgre le filtre des depreciations tierces.
check_logins() {
    local out

    out="$(cd "$ROOT" && docker compose exec -T php wp eval '
require_once ABSPATH . "wp-admin/includes/plugin.php";

wp_set_current_user(1);
set_current_screen("dashboard");
do_action("admin_menu");

global $submenu;
$slug = RapkalinScreen::Logins->slug();
$declare = "";

foreach ((array) $submenu as $parent => $entrees) {
    foreach ((array) $entrees as $entree) {
        if (($entree[2] ?? "") === $slug) {
            $declare = (string) $parent;
        }
    }
}

printf(
    "%s|l ecran Connexions du module est declare sous Comptes|%s\n",
    $declare === "users.php" ? "PASS" : "FAIL",
    $declare === "" ? "ABSENT du menu" : $declare,
);

// Deux couches pour le contributeur : la capacite du module (manage_options)
// et l allow-list de LCDS, qui refuse users.php comme tools.php.
$contributeur = get_role(LCDS_CONTRIBUTOR_ROLE);

printf(
    "%s|les journaux sont refuses au contributeur|%s\n",
    ($contributeur instanceof WP_Role && ! $contributeur->has_cap(RAPKALIN_SECURITY_CAPABILITY)
        && ! LcdsAdminScreen::isAllowed("users.php", array("page" => $slug))
        && ! LcdsAdminScreen::isAllowed("tools.php", array("page" => RapkalinScreen::Errors->slug()))) ? "PASS" : "FAIL",
    RAPKALIN_SECURITY_CAPABILITY . " + allow-list",
);

// Une connexion doit reellement etre inscrite. Le journal est repose ensuite :
// la campagne ne laisse rien derriere elle.
$avant = get_option(RapkalinJournal::ACTIVITY_OPTION, array());
$pending = &rapkalin_security_pending();
$pending["activity"] = array();
$compte = get_userdata(1);
do_action("wp_login", $compte->user_login, $compte);
rapkalin_security_flush();
$apres = get_option(RapkalinJournal::ACTIVITY_OPTION, array());

global $wpdb;
$autoload = $wpdb->get_var($wpdb->prepare("SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", RapkalinJournal::ACTIVITY_OPTION));

update_option(RapkalinJournal::ACTIVITY_OPTION, $avant, false);

$entree = is_array($apres) ? ($apres[0] ?? array()) : array();
$champs = array_keys($entree);
sort($champs);

printf(
    "%s|une connexion est inscrite en tete|%s\n",
    (($entree["event"] ?? "") === RapkalinEvent::LoginSucceeded->value && ($entree["actor"] ?? "") === $compte->user_login) ? "PASS" : "FAIL",
    ($entree["event"] ?? "aucune") . " / " . ($entree["actor"] ?? "-"),
);

// Aucune adresse IP conservee : la question posee est « qui, quand », pas
// « d ou ». Le module ne garde l adresse que hachee, le temps du blocage.
printf(
    "%s|une entree ne porte aucune adresse IP|%s\n",
    $champs === array("actor", "event", "subject", "time") ? "PASS" : "FAIL",
    implode(", ", $champs),
);

printf(
    "%s|le journal n est pas autocharge|%s\n",
    ($autoload === null || ! in_array($autoload, array("yes", "on"), true)) ? "PASS" : "FAIL",
    $autoload === null ? "option absente" : (string) $autoload,
);

// PHP ne garde qu un gestionnaire d erreurs : celui de LCDS (depreciations
// tierces) doit rendre les autres niveaux au journal du module.
ini_set("display_errors", "0");
$pending["errors"] = array();
trigger_error("qa-security-capture", E_USER_WARNING);
$captees = count($pending["errors"]);
$pending["errors"] = array();

printf(
    "%s|une alerte PHP atteint le journal des erreurs|%d captee(s)\n",
    $captees === 1 ? "PASS" : "FAIL",
    $captees,
);
' --allow-root 2>/dev/null | tr -d '\r')"

    if [ -z "$out" ]; then
        printf '  FAIL :: journal des connexions (WP-CLI muet)\n'
        return 1
    fi

    printf '%s\n' "$out" | while IFS='|' read -r verdict label detail; do
        printf '  %s :: %s (%s)\n' "$verdict" "$label" "$detail"
    done

    printf '%s' "$out" | grep -q '^FAIL' && return 1
    return 0
}

# Le champ de reglage de la puce vit dans acf-json/, ecrit a la main : ACF peut
# tres bien refuser un groupe mal forme sans que rien ne le signale cote front.
# On verifie donc qu il le CHARGE, et que ses choix viennent bien de l enum.
check_reglages() {
    local out

    out="$(cd "$ROOT" && docker compose exec -T --user www-data php wp eval '
if (! function_exists("acf_get_field_group")) {
    echo "FAIL|ACF charge le groupe Navigation|ACF absent\n";

    return;
}

$groupe = acf_get_field_group("group_lcds_navigation");

printf(
    "%s|ACF charge le groupe Navigation depuis le JSON|%s\n",
    ($groupe && ($groupe["local"] ?? "") === "json") ? "PASS" : "FAIL",
    $groupe ? ("source " . ($groupe["local"] ?? "base")) : "groupe absent",
);

$champ = acf_get_field("field_lcds_nav_puce");

printf(
    "%s|le champ est sur la page de reglages|%s\n",
    ($champ && $champ["type"] === "button_group") ? "PASS" : "FAIL",
    $champ ? $champ["type"] : "champ absent",
);

$choix = $champ ? $champ["choices"] : array();
$attendu = LcdsDotColor::choices();

printf(
    "%s|les choix viennent de LcdsDotColor|%s\n",
    $choix === $attendu ? "PASS" : "FAIL",
    implode(", ", array_map(fn($k, $v) => "$k=$v", array_keys($choix), $choix)),
);

// Le defaut ACF ne sert que dans le formulaire : tant que rien n est
// enregistre, get_field rend NULL et c est le repli PHP qui decide. Les deux
// doivent donner la MEME couleur, sinon la puce change de teinte au premier
// enregistrement sans que personne ait rien choisi.
//
// On lit le repli, PAS le reglage enregistre : sur un site ou un contributeur
// a deja choisi une couleur, get_field rend son choix et l assertion
// recetterait le contenu de la base au lieu du code.
$repli = lcds_nav_dot_fallback();

printf(
    "%s|le repli du theme et le defaut ACF concordent|%s\n",
    ($champ && $champ["default_value"] === $repli->value) ? "PASS" : "FAIL",
    "ACF " . ($champ["default_value"] ?? "-") . " / theme " . $repli->value,
);
' 2>/dev/null)"

    if [ -z "$out" ]; then
        printf '  FAIL :: reglages du site (aucune sortie)\n'
        return 1
    fi

    printf '%s\n' "$out" | while IFS="|" read -r verdict label detail; do
        [ -z "$verdict" ] && continue
        printf '  %s :: %s (%s)\n' "$verdict" "$label" "$detail"
    done

    printf '%s' "$out" | grep -q '^FAIL' && return 1
    return 0
}

# La CSP part en Report-Only : une source manquante ne bloque rien, elle ne
# laisse qu'un évènement `securitypolicyviolation` dans le navigateur. Seule
# une page réellement chargée peut donc dire qu'il en manque une — voir
# bin/qa/csp.qa.mjs, qui relève aussi les erreurs JavaScript.
#
# Le hero YouTube et le hero vidéo n'existent pas en base : ils sont posés le
# temps des scénarios, puis la rangée est remise à l'identique. La sauvegarde
# vit dans une option et la restauration passe aussi par le `trap` : une
# campagne interrompue ne laisse pas l'accueil modifié.
csp_seed() {
    (cd "$ROOT" && docker compose exec -T -e LCDS_QA_CSP_MODE="$1" php wp eval '
$mode = (string) getenv("LCDS_QA_CSP_MODE");
$pageId = (int) get_option("page_on_front");
$fieldKeys = [
    "video_source" => "field_lcds_hero_video_source",
    "video_youtube" => "field_lcds_hero_youtube",
    "video" => "field_lcds_hero_video",
];
$metaNames = [];

foreach (array_keys($fieldKeys) as $name) {
    $metaNames[] = "sections_0_" . $name;
    $metaNames[] = "_sections_0_" . $name;
}

$backup = get_option("lcds_qa_csp_backup");

if (is_array($backup)) {
    foreach ($metaNames as $metaName) {
        delete_post_meta($pageId, $metaName);

        if (array_key_exists($metaName, $backup["meta"])) {
            update_post_meta($pageId, $metaName, $backup["meta"][$metaName]);
        }
    }

    if ((int) $backup["attachment"] > 0) {
        wp_delete_attachment((int) $backup["attachment"], true);
    }

    delete_option("lcds_qa_csp_backup");
}

if ($mode === "restore") {
    echo "restored";

    return;
}

$sections = get_post_meta($pageId, "sections", true);

if (! is_array($sections) || ($sections[0] ?? "") !== "hero") {
    echo "no-hero";

    return;
}

$saved = [];

foreach ($metaNames as $metaName) {
    if (metadata_exists("post", $pageId, $metaName)) {
        $saved[$metaName] = get_post_meta($pageId, $metaName, true);
    }
}

$attachment = 0;

if ($mode === "file") {
    $upload = wp_upload_bits("qa-csp.mp4", null, "qa");
    $attachment = (int) wp_insert_attachment(
        ["post_mime_type" => "video/mp4", "post_title" => "qa-csp", "post_status" => "inherit"],
        $upload["file"],
    );
}

update_option("lcds_qa_csp_backup", ["meta" => $saved, "attachment" => $attachment], false);

$values = $mode === "youtube"
    ? ["video_source" => "youtube", "video_youtube" => "https://www.youtube.com/watch?v=aqz-KE-bpKQ", "video" => ""]
    : ["video_source" => "file", "video_youtube" => "", "video" => $attachment];

foreach ($values as $name => $value) {
    update_post_meta($pageId, "sections_0_" . $name, $value);
    update_post_meta($pageId, "_sections_0_" . $name, $fieldKeys[$name]);
}

echo "seeded";
' --allow-root 2>/dev/null | tr -d '\r')
}

run_csp_scenarios() {
    local scenarios="$SCRATCH/csp-$1.json"

    printf '%s' "$2" > "$scenarios"
    "$NODE" "$ROOT/bin/qa/csp.qa.mjs" "$CHROME" "$scenarios" | sed 's/^/  /'
    return "${PIPESTATUS[0]}"
}

check_csp() {
    local status=0 seeded no_manager_rules

    NODE="$(command -v node || true)"

    if [ -z "$NODE" ] || ! "$NODE" -e 'process.exit(typeof WebSocket === "function" ? 0 : 1)' 2>/dev/null; then
        printf '  FAIL :: CSP : Node 22 ou plus requis sur l hôte (WebSocket natif)\n'
        return 1
    fi

    # Le gestionnaire de consentement n'a rien à faire là où aucun service
    # tiers n'est intégré : ni script, ni lien « Gérer les cookies ».
    no_manager_rules='"script[src*=tarteaucitron]": false, "[data-consent-manager]": false'

    run_csp_scenarios pages "[
        {\"label\": \"accueil\", \"url\": \"$SITE_URL/?qa-csp=1\", \"expect\": {$no_manager_rules}},
        {\"label\": \"le cabinet\", \"url\": \"$SITE_URL/le-cabinet/?qa-csp=1\", \"expect\": {$no_manager_rules}},
        {\"label\": \"l'équipe\", \"url\": \"$SITE_URL/l-equipe/?qa-csp=1\", \"expect\": {$no_manager_rules}},
        {\"label\": \"conseils\", \"url\": \"$SITE_URL/conseils/?qa-csp=1\", \"expect\": {$no_manager_rules}},
        {\"label\": \"page 404\", \"url\": \"$SITE_URL/qa-csp-page-inexistante/\", \"expect\": {$no_manager_rules}}
    ]" || status=1

    CSP_SEEDED=1
    seeded="$(csp_seed youtube)"

    if [ "$seeded" != "seeded" ]; then
        printf '  FAIL :: CSP : hero YouTube non posé (%s)\n' "${seeded:-WP-CLI muet}"
        status=1
    else
        run_csp_scenarios youtube "[
            {\"label\": \"hero YouTube, premier passage\", \"url\": \"$SITE_URL/?qa-csp=2\",
             \"expect\": {\"#tarteaucitronAlertBig\": true, \"[data-consent-manager]:not([hidden])\": true, \".hero__youtube iframe\": false}},
            {\"label\": \"hero YouTube, accord donné au bandeau\", \"url\": \"$SITE_URL/?qa-csp=3\",
             \"action\": \"tarteaucitron.userInterface.respondAll(true)\", \"settle\": 3000,
             \"expect\": {\".hero__youtube iframe[src^='https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ']\": true, \".hero__youtube.is-ready\": true}},
            {\"label\": \"hero YouTube, accord déjà donné\", \"url\": \"$SITE_URL/?qa-csp=4\", \"consent\": \"!youtube=true\", \"settle\": 3000,
             \"expect\": {\".hero__youtube iframe\": true}},
            {\"label\": \"hero YouTube, refus déjà donné\", \"url\": \"$SITE_URL/?qa-csp=5\", \"consent\": \"!youtube=false\",
             \"expect\": {\".hero__youtube iframe\": false, \"[data-hero-motion]:not([hidden])\": false}}
        ]" || status=1
    fi

    seeded="$(csp_seed file)"

    if [ "$seeded" != "seeded" ]; then
        printf '  FAIL :: CSP : hero vidéo non posé (%s)\n' "${seeded:-WP-CLI muet}"
        status=1
    else
        run_csp_scenarios file "[
            {\"label\": \"hero vidéo de la médiathèque\", \"url\": \"$SITE_URL/?qa-csp=6\",
             \"expect\": {\"video[data-hero-video]\": true, \"[data-hero-motion]:not([hidden])\": true, $no_manager_rules}}
        ]" || status=1
    fi

    if [ "$(csp_seed restore)" = "restored" ]; then
        CSP_SEEDED=0
        printf '  PASS :: CSP : rangée du hero remise à l identique\n'
    else
        printf '  FAIL :: CSP : rangée du hero NON restaurée (option lcds_qa_csp_backup)\n'
        status=1
    fi

    return "$status"
}

echo "== Rôle de contribution =="
check_role || FAILURES=$((FAILURES + 1))

echo "== Journal des connexions =="
check_logins || FAILURES=$((FAILURES + 1))

echo "== Réglages du site =="
check_reglages || FAILURES=$((FAILURES + 1))

echo "== Contribution de la page d'accueil =="
check_contribution || FAILURES=$((FAILURES + 1))

echo "== Accessibilité côté serveur =="
check_a11y_serveur || FAILURES=$((FAILURES + 1))

echo "== Navigation amorcée =="
check_menus || FAILURES=$((FAILURES + 1))

echo "== Conversion WebP =="
check_webp || FAILURES=$((FAILURES + 1))

echo "== CSP et erreurs JavaScript =="
check_csp || FAILURES=$((FAILURES + 1))

for width in 1440 500 320; do
    echo "== Front à ${width}px =="
    report "$width" "$(dump_dom "$width")" || FAILURES=$((FAILURES + 1))
done

# Les autres pages passent la MEME campagne : les assertions qui ne trouvent pas
# leur noeud se taisent, celles qui sont communes — repères, contrastes, plan de
# titres, chargement différé — valent partout.
# Les pages intérieures passent les TROIS largeurs, comme l'accueil. À 1440
# elles portent leurs cotes de maquette ; à 500 et 320 elles n'en ont aucune —
# aucune maquette mobile n'existe —, mais les assertions structurelles valent :
# débordement horizontal, coupures dans les mots, contrastes, plan de titres.
#
# Ajouté après coup, et pour cause : le titre de « L'équipe » gardait à 320 une
# largeur relevée à 1440 et se coupait au milieu d'un mot. La campagne ne
# regardait alors ces pages qu'à 1440.
for width in 1440 500 320; do
    echo "== Page « Le cabinet » à ${width}px =="
    report "$width" "$(dump_dom "$width" /le-cabinet/)" || FAILURES=$((FAILURES + 1))
done

for width in 1440 500 320; do
    echo "== Page « L'équipe » à ${width}px =="
    report "$width" "$(dump_dom "$width" /l-equipe/)" || FAILURES=$((FAILURES + 1))
done

for width in 1440 500 320; do
    echo "== Page « Conseils » à ${width}px =="
    report "$width" "$(dump_dom "$width" /conseils/)" || FAILURES=$((FAILURES + 1))
done

echo
if [ "$FAILURES" -eq 0 ]; then
    echo "QA front : tout est au vert."
    exit 0
fi

echo "QA front : $FAILURES bloc(s) en échec."
exit 1
