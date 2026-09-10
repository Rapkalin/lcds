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
    #
    # --virtual-time-budget compte le temps VIRTUEL, que chaque `setTimeout` de
    # la campagne avance d'un coup. Les épreuves du verrou de molette en
    # consomment une dizaine de secondes à elles seules ; à 8000 la campagne
    # n'atteignait plus sa fin et le pilote ne trouvait aucun résultat. Le
    # relever ne coûte pas de temps réel, ce budget n'étant pas une attente.
    "$CHROME" --headless --disable-gpu --no-first-run --no-default-browser-check \
        --force-prefers-reduced-motion \
        --window-size=1600,900 --virtual-time-budget=60000 --dump-dom \
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

check_logins() {
    local out

    out="$(cd "$ROOT" && docker compose exec -T php wp eval '
require_once ABSPATH . "wp-admin/includes/plugin.php";

// L ecran doit exister pour un administrateur, et sous « Comptes » : la
// fonction add_users_page() du coeur le rattacherait a profile.php.
wp_set_current_user(1);
set_current_screen("dashboard");
do_action("admin_menu");

global $submenu;
$declare = "";

foreach ((array) $submenu as $parent => $entrees) {
    foreach ((array) $entrees as $entree) {
        if (($entree[2] ?? "") === LCDS_LOGIN_LOG_SCREEN) {
            $declare = (string) $parent;
        }
    }
}

printf(
    "%s|l ecran est declare sous Comptes|%s\n",
    $declare === "users.php" ? "PASS" : "FAIL",
    $declare === "" ? "ABSENT du menu" : $declare,
);

// Et il doit rester hors de portee du contributeur : l allow-list refuse
// users.php, donc toute page qui s y accroche.
printf(
    "%s|l ecran est refuse au contributeur|%s\n",
    LcdsAdminScreen::isAllowed("users.php", array("page" => LCDS_LOGIN_LOG_SCREEN)) ? "FAIL" : "PASS",
    "allow-list",
);

// Une connexion doit reellement etre inscrite. On repose le journal ensuite :
// la campagne ne laisse rien derriere elle.
$avant = get_option(LCDS_LOGIN_LOG_OPTION, array());
$compte = get_userdata(1);
do_action("wp_login", $compte->user_login, $compte);
$apres = lcds_login_log();

// Lu AVANT la remise en etat : la restauration ecrit elle-meme autoload=off,
// et l assertion ne verifiait alors que sa propre ligne.
global $wpdb;
$autoload = $wpdb->get_var($wpdb->prepare("SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", LCDS_LOGIN_LOG_OPTION));

// La colonne de la liste des comptes, tant que le journal porte l entree :
// lcds_last_logins() memorise son calcul pour la duree de la requete.
$colonnes = apply_filters("manage_users_columns", array("username" => "Identifiant"));
$cellule = apply_filters("manage_users_custom_column", "", LCDS_LOGIN_LOG_SCREEN, 1);
$jamais = apply_filters("manage_users_custom_column", "", LCDS_LOGIN_LOG_SCREEN, 987654);
$intacte = apply_filters("manage_users_custom_column", "posts-a-moi", "posts", 1);

update_option(LCDS_LOGIN_LOG_OPTION, $avant, false);

printf(
    "%s|la liste des comptes porte la colonne|%s\n",
    isset($colonnes[LCDS_LOGIN_LOG_SCREEN]) ? "PASS" : "FAIL",
    implode(", ", array_keys($colonnes)),
);

printf(
    "%s|la colonne affiche une date pour un compte connecte|%s\n",
    ($cellule !== "" && ! str_contains($cellule, "mdash")) ? "PASS" : "FAIL",
    $cellule === "" ? "VIDE" : wp_strip_all_tags($cellule),
);

// Un tiret seul se lit « tiret » a la synthese vocale : le sens doit etre
// porte par un texte reserve aux lecteurs d ecran.
printf(
    "%s|un compte jamais connecte est annonce, pas seulement tirete|%s\n",
    (str_contains($jamais, "screen-reader-text") && str_contains($jamais, "aria-hidden")) ? "PASS" : "FAIL",
    wp_strip_all_tags($jamais),
);

printf(
    "%s|la colonne ne touche aucune autre colonne|%s\n",
    $intacte === "posts-a-moi" ? "PASS" : "FAIL",
    $intacte,
);

printf(
    "%s|une connexion est inscrite en tete|%s\n",
    (count($apres) === count($avant) + 1 && ($apres[0]["id"] ?? 0) === 1) ? "PASS" : "FAIL",
    count($avant) . " puis " . count($apres) . " entrees",
);

// La pagination : 25 entrees fabriquees, rendues page par page. Le journal
// est repose juste apres.
$fabrique = array();

for ($rang = 1; $rang <= 25; $rang++) {
    $fabrique[] = array("id" => 1, "login" => "admin", "time" => time() - $rang);
}

update_option(LCDS_LOGIN_LOG_OPTION, $fabrique, false);
$rendu = array();

foreach (array(1, 2, 99) as $numero) {
    $_GET["paged"] = $numero;
    ob_start();
    lcds_render_login_screen();
    $rendu[$numero] = (string) ob_get_clean();
}

unset($_GET["paged"]);
update_option(LCDS_LOGIN_LOG_OPTION, $avant, false);

$lignes = static fn (string $html): int => substr_count($html, "<tr>") - 1;

printf(
    "%s|la premiere page montre %d connexions|%d\n",
    $lignes($rendu[1]) === LcdsLoginLog::PER_PAGE ? "PASS" : "FAIL",
    LcdsLoginLog::PER_PAGE,
    $lignes($rendu[1]),
);

printf(
    "%s|la seconde page montre le reste|%d\n",
    $lignes($rendu[2]) === 25 - LcdsLoginLog::PER_PAGE ? "PASS" : "FAIL",
    $lignes($rendu[2]),
);

// Un numero de page vient de l URL : il est hostile par principe.
printf(
    "%s|une page hors bornes retombe sur la derniere|%d\n",
    $lignes($rendu[99]) === $lignes($rendu[2]) ? "PASS" : "FAIL",
    $lignes($rendu[99]),
);

// Deux blocs de pagination sur la page : sans nom, rien ne les distingue a la
// synthese vocale.
printf(
    "%s|la pagination est un systeme de navigation nomme|%d bloc(s)\n",
    substr_count($rendu[1], "<nav class=\"tablenav-pages\" aria-label=") === 2 ? "PASS" : "FAIL",
    substr_count($rendu[1], "<nav class=\"tablenav-pages\""),
);

// Et elle disparait quand il n y a qu une page.
update_option(LCDS_LOGIN_LOG_OPTION, array_slice($fabrique, 0, 5), false);
ob_start();
lcds_render_login_screen();
$courte = (string) ob_get_clean();
update_option(LCDS_LOGIN_LOG_OPTION, $avant, false);

printf(
    "%s|aucune pagination sur une seule page|%s\n",
    ! str_contains($courte, "tablenav-pages") ? "PASS" : "FAIL",
    str_contains($courte, "tablenav-pages") ? "AFFICHEE" : "absente",
);

// L option ne doit pas etre autochargee : elle serait lue a CHAQUE requete du
// site, pour un ecran que seul un administrateur ouvre.
printf(
    "%s|le journal n est pas autocharge|%s\n",
    ($autoload === null || ! in_array($autoload, array("yes", "on"), true)) ? "PASS" : "FAIL",
    $autoload === null ? "option absente" : (string) $autoload,
);

// Aucune adresse IP : la question posee est « qui, quand », pas « d ou ».
$champs = $apres === array() ? array() : array_keys($apres[0]);
sort($champs);

printf(
    "%s|une entree ne porte que compte et instant|%s\n",
    $champs === array("id", "login", "time") ? "PASS" : "FAIL",
    implode(", ", $champs),
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
