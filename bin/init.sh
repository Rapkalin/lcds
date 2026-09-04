#!/usr/bin/env bash
# =============================================================================
# Initialisation WordPress (exécutée par l'entrypoint du conteneur PHP).
#
# IDEMPOTENT : si WordPress est déjà installé, l'installation initiale est
# sautée. C'est ce qui permet d'importer un dump existant sans que le script
# n'écrase quoi que ce soit au redémarrage suivant.
#
# wp cible website/wordpress-core grâce à wp-cli.yml (path:) à la racine.
# Le script tourne en root dans le conteneur -> toutes les commandes wp ont
# besoin de --allow-root.
# =============================================================================
set -e

APP_DIR="/var/www/html"
cd "$APP_DIR"

# Le .env vit dans le docroot sur le serveur (website/.env, lien vers
# shared/.env) et à la racine du dépôt en local.
if [ -f "$APP_DIR/website/.env" ]; then
    ENV_FILE="$APP_DIR/website/.env"
else
    ENV_FILE="$APP_DIR/.env"
fi

echo "==> [init] Initialisation WordPress"

if [ -f "$ENV_FILE" ]; then
    # set -a : toute variable affectée est exportée automatiquement.
    set -a
    # shellcheck disable=SC1090
    . "$ENV_FILE"
    set +a
fi

WP_HOME="${WP_HOME:-http://localhost:8080}"
WP_TITLE="${WP_TITLE:-La Clinique du Sourire}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-admin}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@lcds.local}"

# -----------------------------------------------------------------------------
# 1) Générer les sels encore à 'generateme' dans le .env.
#    Chaînes alphanumériques de 64 caractères : sans | ni /, donc sûres pour sed.
# -----------------------------------------------------------------------------
SALT_KEYS="AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT"

if [ -f "$ENV_FILE" ]; then
    for KEY in $SALT_KEYS; do
        if grep -q "^${KEY}='generateme'" "$ENV_FILE"; then
            SALT="$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c 64)"
            sed -i "s|^${KEY}='generateme'|${KEY}='${SALT}'|" "$ENV_FILE"
            echo "==> [init] Sel généré pour ${KEY}"
        fi
    done
else
    echo "!!! [init] .env introuvable : génération des sels ignorée."
fi

# -----------------------------------------------------------------------------
# 2) Droits d'écriture. uploads/ est un volume nommé (donc réellement chownable) ;
#    les autres sont sur le bind mount, où le chown peut échouer selon l'hôte —
#    on tolère l'échec plutôt que de bloquer le démarrage.
# -----------------------------------------------------------------------------
echo "==> [init] Droits d'écriture sur les dossiers de contenu"
for DIR in website/app/uploads website/app/cache website/app/languages; do
    mkdir -p "$APP_DIR/$DIR"
    chown -R www-data:www-data "$APP_DIR/$DIR" 2>/dev/null || true
    chmod -R u+rwX,g+rwX "$APP_DIR/$DIR" 2>/dev/null || true
done

# -----------------------------------------------------------------------------
# 3) Installation initiale : UNIQUEMENT si WordPress n'est pas encore installé.
#    Aucune création de contenu ici : le site a déjà ses pages, et un dump
#    importé ne doit jamais être complété par des contenus de démo.
# -----------------------------------------------------------------------------
if wp core is-installed --allow-root 2>/dev/null; then
    echo "==> [init] WordPress déjà installé : installation initiale sautée."
else
    echo "==> [init] Installation de WordPress sur ${WP_HOME}"
    wp core install \
        --url="$WP_HOME" \
        --title="$WP_TITLE" \
        --admin_user="$WP_ADMIN_USER" \
        --admin_password="$WP_ADMIN_PASSWORD" \
        --admin_email="$WP_ADMIN_EMAIL" \
        --skip-email \
        --allow-root

    # `wp core install` écrit la MÊME valeur dans home et siteurl, ce qui laisse
    # un `home` faux (suffixé /wordpress-core). Sans effet au runtime — les
    # constantes WP_HOME/WP_SITEURL priment — mais trompeur dès qu'on inspecte la
    # base, et faux si le dump est réimporté ailleurs sans ces constantes.
    # On écrit via $wpdb : `wp option update` serait un no-op, update_option()
    # comparant la nouvelle valeur à celle renvoyée par la constante.
    wp eval '
        global $wpdb;
        $wpdb->update($wpdb->options, ["option_value" => WP_HOME], ["option_name" => "home"]);
        $wpdb->update($wpdb->options, ["option_value" => WP_SITEURL], ["option_name" => "siteurl"]);
    ' --allow-root

    echo "==> [init] Permaliens"
    wp rewrite structure '/%postname%/' --hard --allow-root
    wp rewrite flush --hard --allow-root
fi

# -----------------------------------------------------------------------------
# 4) Thème : activé à chaque démarrage (idempotent).
# -----------------------------------------------------------------------------
if wp theme is-installed lcds --allow-root 2>/dev/null; then
    wp theme activate lcds --allow-root >/dev/null 2>&1 || true
    echo "==> [init] Thème 'lcds' actif."
else
    echo "!!! [init] Thème 'lcds' introuvable (ignoré)."
fi

# -----------------------------------------------------------------------------
# 5) Plugins. Activation réconciliée à chaque démarrage : un plugin installé par
#    Composer n'est pas actif pour autant, et DISALLOW_FILE_MODS n'empêche pas
#    l'activation d'un plugin déjà présent sur le disque.
#    ACF Pro n'est pas géré par Composer (licence) : activé s'il est là, ignoré
#    sinon — voir readme/installation.md.
#
#    Les traductions des plugins sont installées AVANT l'activation, et cet
#    ordre compte : Yoast écrit ses gabarits de titre par défaut au moment où
#    il s'active. Activé sans son paquet de langue, il y range les chaînes
#    anglaises, et installer la traduction ensuite ne réécrit rien.
# -----------------------------------------------------------------------------
wp language plugin install --all fr_FR --allow-root >/dev/null 2>&1 \
    && echo "==> [init] Traductions des plugins installées." \
    || echo "==> [init] Traductions des plugins : rien à installer."

for PLUGIN in wordpress-seo advanced-custom-fields-pro; do
    if wp plugin is-installed "$PLUGIN" --allow-root 2>/dev/null; then
        wp plugin activate "$PLUGIN" --allow-root >/dev/null 2>&1 \
            && echo "==> [init] Plugin '${PLUGIN}' actif." \
            || echo "!!! [init] Activation de '${PLUGIN}' échouée (ignoré)."
    else
        echo "==> [init] Plugin '${PLUGIN}' absent (ignoré)."
    fi
done

# Rattrapage pour les environnements déjà installés, où l'ordre ci-dessus n'a
# pas pu jouer : seules les valeurs identiques au défaut ANGLAIS de Yoast sont
# retirées, un gabarit saisi par un contributeur ne peut pas être touché — voir
# readme/seo.md.
wp eval 'lcds_reset_seo_titles();' --allow-root >/dev/null 2>&1 \
    && echo "==> [init] Gabarits de titre Yoast vérifiés." \
    || echo "!!! [init] Correction des gabarits de titre échouée (ignoré)."

# -----------------------------------------------------------------------------
# 6) Navigation : les menus, leur rattachement aux emplacements, et leurs
#    entrées par défaut. Sans ça l'en-tête sort vide — wp_nav_menu() est appelé
#    avec `fallback_cb => false`. Idempotent, et un menu déjà garni par un
#    contributeur n'est jamais retouché — voir readme/menus.md.
# -----------------------------------------------------------------------------
echo "==> [init] Navigation"
wp eval 'lcds_seed_default_menus();' --allow-root \
    && echo "==> [init] Menus amorcés." \
    || echo "!!! [init] Amorçage des menus échoué (ignoré)."

# -----------------------------------------------------------------------------
# 7) Page d'accueil : la page, ses quatre blocs de section, et le réglage qui la
#    désigne comme accueil du site. Idempotent — une page déjà en place n'est
#    jamais réécrite, le contenu d'un contributeur ne doit pas disparaître au
#    redémarrage d'un conteneur.
#
#    APRÈS l'activation des plugins, impérativement : l'amorçage résout les clés
#    de champ par acf_get_fields(), qui n'existe pas si ACF n'est pas encore
#    actif. Joué trop tôt, il créait les quatre blocs SANS AUCUNE DONNÉE, et son
#    idempotence interdisait toute correction au démarrage suivant.
# -----------------------------------------------------------------------------
echo "==> [init] Page d'accueil"
wp eval-file "$APP_DIR/bin/seed-homepage.php" --allow-root || \
    echo "!!! [init] Amorçage de la page d'accueil échoué (ignoré)."

# -----------------------------------------------------------------------------
# 8) Cache pleine page (WP Super Cache) : réconcilié avec WP_CACHE à chaque
#    démarrage. Livré désactivé. L'activation génère le drop-in
#    website/app/advanced-cache.php — voir readme/cache.md.
# -----------------------------------------------------------------------------
if [ "${WP_CACHE}" = "true" ]; then
    echo "==> [init] WP_CACHE=true -> activation de WP Super Cache"
    wp plugin activate wp-super-cache --allow-root >/dev/null 2>&1 \
        || echo "!!! [init] Activation de WP Super Cache échouée (ignoré)."
else
    wp plugin deactivate wp-super-cache --allow-root >/dev/null 2>&1 || true
fi

# -----------------------------------------------------------------------------
# 9) Langue du site : fr_FR à chaque démarrage. Les packs de langue sont
#    téléchargés s'ils manquent (idempotent).
# -----------------------------------------------------------------------------
echo "==> [init] Langue du site : fr_FR"
if ! wp language core is-installed fr_FR --allow-root 2>/dev/null; then
    wp language core install fr_FR --allow-root >/dev/null 2>&1 \
        || echo "!!! [init] Installation de fr_FR échouée (ignoré)."
fi
wp option update WPLANG fr_FR --allow-root >/dev/null 2>&1 \
    || echo "!!! [init] Réglage de la langue échoué (ignoré)."

echo "==> [init] Initialisation terminée."
echo "    Front : ${WP_HOME}"
echo "    Admin : ${WP_HOME}/wordpress-core/wp-admin"
