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
dump_dom() {
    local width="$1" out="$SCRATCH/dump-$width.html" pid attempts=0

    # --force-prefers-reduced-motion : rend le défilement du carrousel
    # instantané, donc mesurable. Sans cela rien n'est déterministe.
    "$CHROME" --headless --disable-gpu --no-first-run --no-default-browser-check \
        --force-prefers-reduced-motion \
        --window-size="$width,900" --virtual-time-budget=8000 --dump-dom \
        --user-data-dir="$SCRATCH/profile-$width" \
        "$SITE_URL/app/themes/lcds/dist/$HARNESS_NAME" > "$out" 2>/dev/null &
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

for width in 1440 500; do
    echo "== En-tête à ${width}px =="
    report "$width" "$(dump_dom "$width")" || FAILURES=$((FAILURES + 1))
done

echo
if [ "$FAILURES" -eq 0 ]; then
    echo "QA front : tout est au vert."
    exit 0
fi

echo "QA front : $FAILURES bloc(s) en échec."
exit 1
