# Raccourcis Docker pour LCDS.
# Charger dans le shell courant :  source aliases.sh
#
# Les identifiants sont ceux du .env.example (dev). À ajuster si le .env local
# utilise d'autres valeurs pour DB_NAME / DB_USER / DB_PASSWORD.

# ─────────────────────────────────────────────────────────────
# Application
# ─────────────────────────────────────────────────────────────
alias dphp='docker compose exec php'
alias dcomposer='docker compose exec php composer'
alias dwp='docker compose exec php wp --allow-root'
alias dlogs='docker compose logs -f php'

# Qualité (dans le conteneur : même version de PHP que la CI)
alias dcheck='docker compose exec php composer check'
alias dtest='docker compose exec php composer test'

# Front (profil "tools", conteneur jetable)
alias dnpm='docker compose run --rm node npm'

# ─────────────────────────────────────────────────────────────
# Base de données (MySQL 8.4)
#
# Tout passe par le client MySQL du conteneur `db` : c'est le seul client qui
# accepte le certificat auto-signé de MySQL 8.4. Ces alias REMPLACENT
# `wp db query` / `wp db export` / `wp db import`, inopérantes depuis le
# conteneur php — voir readme/docker.md.
#
# MYSQL_PWD transmet le mot de passe par l'environnement : pas de `-p` sur la
# ligne de commande, donc pas d'avertissement à chaque appel.
#
# db-drop / db-create passent par `root` : l'utilisateur applicatif n'a de
# privilèges que SUR la base `lcds`, pas celui d'en créer une.
# ─────────────────────────────────────────────────────────────
alias db-cli='docker compose exec -e MYSQL_PWD=lcds db mysql -u lcds -h db lcds'
alias db-query='docker compose exec -T -e MYSQL_PWD=lcds db mysql -u lcds -h db lcds -e '
alias db-drop='docker compose exec -T -e MYSQL_PWD=root db mysqladmin -h db -u root -f drop lcds'
alias db-create='docker compose exec -T -e MYSQL_PWD=root db mysql -u root -h db -e "CREATE DATABASE IF NOT EXISTS lcds CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"'
alias db-import='docker compose exec -T -e MYSQL_PWD=lcds db mysql -u lcds -h db lcds < '
alias db-export='docker compose exec -T -e MYSQL_PWD=lcds db mysqldump --no-tablespaces -u lcds lcds > '

# Exemples :
#   db-export dump.sql
#   db-drop && db-create && db-import dump.sql
#   db-query "SELECT option_value FROM wp_options WHERE option_name='home'"
#   db-cli                       # shell MySQL interactif
#   dwp search-replace 'http://lcds.local' 'http://localhost:8020' --all-tables
