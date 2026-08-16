#!/usr/bin/env bash
# =============================================================================
# Entrypoint du conteneur PHP/Apache.
# Séquence : dépendances Composer -> .env -> attente base -> init WP -> apache.
# Idempotent : peut être relancé sans casser une installation existante.
# Le processus final (apache2-foreground) vient du CMD du Dockerfile.
# =============================================================================
set -e

APP_DIR="/var/www/html"
cd "$APP_DIR"

# Le .env vit à la racine du dépôt en local, dans le docroot sur le serveur
# (website/.env, lien vers shared/.env). shared/ est un concept SERVEUR : il n'a
# pas à exister sur un poste de dev.
if [ -f "$APP_DIR/website/.env" ]; then
    ENV_FILE="$APP_DIR/website/.env"
else
    ENV_FILE="$APP_DIR/.env"
fi

echo "==> [entrypoint] Démarrage du conteneur PHP/Apache"

# -----------------------------------------------------------------------------
# 1) Dépendances Composer. Le vendor-dir du projet est website/vendor (et non
#    vendor/ à la racine) — voir composer.json, config.vendor-dir.
# -----------------------------------------------------------------------------
if [ ! -d "$APP_DIR/website/vendor" ]; then
    echo "==> [entrypoint] website/vendor absent : composer install"
    composer install --no-interaction --prefer-dist
else
    echo "==> [entrypoint] website/vendor présent : composer install ignoré"
fi

# -----------------------------------------------------------------------------
# 2) Fichier .env (copié depuis .env.example s'il manque)
# -----------------------------------------------------------------------------
if [ ! -f "$ENV_FILE" ]; then
    echo "==> [entrypoint] .env absent : copie depuis .env.example"
    cp "$APP_DIR/.env.example" "$ENV_FILE"
fi

# -----------------------------------------------------------------------------
# 3) Les variables d'environnement fournies par compose ou la plateforme
#    PRIMENT sur le .env. On les y recopie pour que tout le monde lise les mêmes
#    valeurs : PHP (phpdotenv) ET bin/init.sh (shell).
#    Les sels sont inclus : fournis par l'environnement (secret partagé), ils
#    sont écrits dans le .env pour que toutes les instances servant le même site
#    utilisent les MÊMES sels (cookies et nonces restent valides). Absents, ils
#    restent à 'generateme' et bin/init.sh les génère une fois.
# -----------------------------------------------------------------------------
update_env() {
    local key="$1" val="$2" file="$ENV_FILE"
    # Délimiteur sed '|' : absent des URLs et identifiants usuels.
    if grep -qE "^${key}=" "$file"; then
        sed -i "s|^${key}=.*|${key}='${val}'|" "$file"
    else
        echo "${key}='${val}'" >> "$file"
    fi
}

for KEY in WP_ENV WP_HOME WP_SITEURL DB_NAME DB_USER DB_PASSWORD DB_HOST DB_PREFIX \
           WP_TITLE WP_ADMIN_USER WP_ADMIN_PASSWORD WP_ADMIN_EMAIL \
           WP_CACHE DISABLE_WP_CRON \
           AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY \
           AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT; do
    # Indirection bash : ${!KEY} = valeur de la variable nommée par $KEY.
    if [ -n "${!KEY+x}" ] && [ -n "${!KEY}" ]; then
        update_env "$KEY" "${!KEY}"
        echo "==> [entrypoint] .env : ${KEY} surchargé depuis l'environnement"
    fi
done

# -----------------------------------------------------------------------------
# 4) Charger le .env (désormais cohérent) pour les sous-processus.
#    `set -a` les exporte automatiquement.
# -----------------------------------------------------------------------------
set -a
# shellcheck disable=SC1091
. "$ENV_FILE"
set +a

# -----------------------------------------------------------------------------
# 5) Attente de la base (max ~60 s).
#    On teste via mysqli, exactement le chemin qu'emprunte WordPress : cela gère
#    caching_sha2_password, contrairement au client mysqladmin.
# -----------------------------------------------------------------------------
export DB_HOST="${DB_HOST:-db}"
export DB_USER="${DB_USER:-lcds}"
export DB_PASSWORD="${DB_PASSWORD:-lcds}"
echo "==> [entrypoint] Attente de la base sur '${DB_HOST}'..."

ATTEMPTS=0
MAX_ATTEMPTS=30   # 30 x 2s = 60s
until php -r '$c=@mysqli_connect(getenv("DB_HOST"),getenv("DB_USER"),getenv("DB_PASSWORD")); exit($c ? 0 : 1);' >/dev/null 2>&1; do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [ "$ATTEMPTS" -ge "$MAX_ATTEMPTS" ]; then
        echo "!!! [entrypoint] La base n'a pas répondu après ~60 s. Abandon."
        exit 1
    fi
    echo "    ... base pas encore prête (tentative ${ATTEMPTS}/${MAX_ATTEMPTS})"
    sleep 2
done
echo "==> [entrypoint] Base joignable."

# -----------------------------------------------------------------------------
# 6) Initialisation WordPress (idempotente)
# -----------------------------------------------------------------------------
echo "==> [entrypoint] Exécution de bin/init.sh"
bash "$APP_DIR/bin/init.sh"

# -----------------------------------------------------------------------------
# 7) Démarrage du processus principal (apache via CMD)
# -----------------------------------------------------------------------------
echo "==> [entrypoint] Initialisation terminée. Démarrage : $*"
exec "$@"
