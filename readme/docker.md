# Docker

## Services

| Service | Image | Rôle | Port hôte |
| --- | --- | --- | --- |
| `php` | construite (`docker/php/Dockerfile`) | PHP 8.4 + Apache, sert le docroot `website/` | `APP_PORT` (8020) |
| `db` | `mysql:8.4` | Base de données | `DB_PORT` (3307) |
| `phpmyadmin` | `phpmyadmin` | Interface base | `PMA_PORT` (8021) |
| `mailpit` | `axllent/mailpit` | Capture des mails sortants | `MAILPIT_PORT` (8025) |
| `node` | `node:20` | Build du thème (profil `tools`) | — |

Le service `node` porte le profil `tools` : il ne démarre **pas** avec
`docker compose up`, on l'invoque à la demande
(`docker compose run --rm node npm run build`).

MySQL est publié sur **3307** et non 3306, pour cohabiter avec un MySQL local
(MAMP) sans conflit.

## Choix d'architecture

**Le projet est bind-monté, pas copié dans l'image.** `./:/var/www/html` : le
code édité sur l'hôte est servi immédiatement, `website/vendor` reste visible par
l'IDE et par le hook de pré-commit, et aucun rebuild n'est nécessaire pour une
modification de code. Un rebuild n'est requis que si le `Dockerfile` change.

**Les médias sont dans un volume nommé** (`uploads`), pas sur le bind mount :
ils survivent à un `docker compose down` et restent réellement inscriptibles par
`www-data` (un `chown` sur un bind mount macOS n'a pas toujours d'effet).

**TLS reste actif sur MySQL** (le défaut de la 8.4). Le couper n'est pas une
option : `caching_sha2_password` refuse l'authentification par mot de passe sur
une connexion TCP en clair, donc plus rien ne se connecterait — WordPress
compris. Conséquence à connaître, voir « Limitation `wp db` » ci-dessous.

**Aucune variable dans `docker-compose.yml`.** Toute la configuration vit dans
`shared/.env`, lu par phpdotenv côté PHP et sourcé par `bin/init.sh` côté shell.
Un bloc `environment:` créerait une seconde source de vérité qui divergerait. Le
`.env` à la racine est un simple **lien** vers `shared/.env` : Docker Compose ne
sait interpoler `${APP_PORT}` qu'à partir de la racine.

**`shared/` reproduit en local l'arborescence du serveur** (`.env`, plugins sous
licence). Le même mécanisme de liens tourne des deux côtés — `bin/init.sh` en
local, le workflow de déploiement sur le serveur — donc un plugin payant se
comporte pareil partout. Voir [`deploiement.md`](deploiement.md).

**Les en-têtes de sécurité ne sont PAS dans le vhost du conteneur.** Ils vivent
dans `website/.htaccess`, qui part avec le code en production ; le vhost se
contente de `AllowOverride All`. Les dupliquer donnerait deux CSP à maintenir.
Voir [`securite.md`](securite.md).

**Images publiques uniquement.** Le dépôt est sur GitHub et doit pouvoir être
construit par n'importe qui : pas de registre privé, pas d'image de plugins
pré-packagés.

## Cycle de vie

L'entrypoint (`docker/php/entrypoint.sh`) est **idempotent** et rejoué à chaque
démarrage :

1. `composer install` si `website/vendor` est absent ;
2. `.env` copié depuis `.env.example` s'il manque ;
3. les variables d'environnement du conteneur sont recopiées dans le `.env` (elles
   priment) ;
4. attente de la base (60 s max), testée via `mysqli` — le chemin exact
   qu'emprunte WordPress ;
5. `bin/init.sh` ;
6. `apache2-foreground`.

`bin/init.sh` est lui aussi idempotent :

- génère les sels encore à `generateme` ;
- pose les droits sur `uploads/`, `uploads-webpc/`, `cache/`, `languages/` ;
- **installe WordPress uniquement s'il ne l'est pas déjà** — aucun contenu de démo
  n'est créé, pour qu'un dump importé ne soit jamais complété ni écrasé ;
- réconcilie à chaque démarrage : thème actif, plugins activés, WP Super Cache
  aligné sur `WP_CACHE`, langue `fr_FR`.

## Commandes

Voir [`commandes.md`](commandes.md). Le plus rapide reste `source aliases.sh`,
qui expose `dphp`, `dcomposer`, `dwp`, `dcheck`, `dtest`, `dnpm`, `db-import`,
`db-export`, `db-drop`.

## Limitation `wp db` (connue, contournée)

Trois sous-commandes WP-CLI **ne fonctionnent pas** dans ce conteneur :
`wp db query`, `wp db export`, `wp db import`.

Elles ne passent pas par PHP : elles délèguent au client MySQL en ligne de
commande. L'image Debian trixie ne fournit que le client **MariaDB**, qui vérifie
la chaîne de certificats par défaut et rejette le certificat auto-signé que MySQL
8.4 génère — et WP-CLI l'invoque avec `--no-defaults`, ce qui neutralise le
`~/.my.cnf` qui désactiverait la vérification. Le dépôt APT MySQL ne publie pas
encore de paquet pour Debian trixie, il n'y a donc pas de vrai client MySQL à
installer.

**Chacune a un alias de remplacement** dans `aliases.sh`, qui passe par le client
MySQL du conteneur `db` :

| À la place de | Utiliser |
| --- | --- |
| `wp db query "…"` | `db-query "…"` |
| `wp db export dump.sql` | `db-export dump.sql` |
| `wp db import dump.sql` | `db-import dump.sql` |
| `wp db reset` | `db-drop && db-create` |
| *(shell interactif)* | `db-cli` |

**Tout le reste de WP-CLI marche**, y compris ce qui compte pour une migration :
`dwp search-replace …` (PHP pur), `dwp db tables`, `dwp db size`, `dwp option`,
`dwp plugin`, `dwp theme`, `dwp core`… phpMyAdmin reste disponible pour explorer
la base à la souris.

## Dépannage

| Symptôme | Cause probable |
| --- | --- |
| `port is already allocated` | Un service local occupe le port → changer `APP_PORT` / `DB_PORT` / `PMA_PORT` dans le `.env`. |
| Le conteneur `php` redémarre en boucle | La base n'a pas répondu en 60 s. `docker compose logs db`, puis `docker compose up -d` à nouveau. |
| Styles absents, redirections en boucle | `WP_HOME` ne correspond pas à l'URL réellement utilisée, ou la base contient encore l'ancien domaine → `wp search-replace`. |
| `dist/main.css` en 404 | Le front n'a pas été compilé → `docker compose run --rm node npm run build`. |
| Contenus vides partout | ACF Pro n'est pas installé — voir [`installation.md`](installation.md). |
| Une modification PHP n'est pas prise en compte | Rare (`opcache.revalidate_freq=0`), mais `docker compose restart php` tranche. |
| Erreur d'écriture dans `uploads/` | `docker compose exec php chown -R www-data:www-data website/app/uploads` |
