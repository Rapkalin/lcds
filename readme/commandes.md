# Commandes

Le projet tourne dans Docker : tout s'exécute dans le conteneur `php`.
`source aliases.sh` expose les raccourcis de la dernière colonne.

## Environnement

| Commande | Effet | Alias |
| --- | --- | --- |
| `docker compose up -d` | Démarre la stack. | — |
| `docker compose down` | Arrête la stack (données conservées). | — |
| `docker compose down -v` | Arrête **et supprime base + médias**. | — |
| `docker compose logs -f php` | Suit les logs applicatifs. | `dlogs` |
| `docker compose restart php` | Redémarre PHP/Apache (rejoue `bin/init.sh`). | — |
| `docker compose build php` | Reconstruit l'image (après modif du Dockerfile). | — |
| `docker compose exec php bash` | Shell dans le conteneur. | — |

## Qualité & tests

| Commande | Effet | Alias |
| --- | --- | --- |
| `docker compose exec php composer check` | lint + types + stan. **Gate du pré-commit et de la CI.** | `dcheck` |
| `docker compose exec php composer lint` | Style PHP (Pint), vérifie sans modifier. | — |
| `docker compose exec php composer lint:fix` | Style PHP, corrige. | — |
| `docker compose exec php composer types` | Types natifs (PHPCS/Slevomat). | — |
| `docker compose exec php composer types:fix` | Ajoute les types déductibles. | — |
| `docker compose exec php composer stan` | PHPStan niveau 6. | — |
| `docker compose exec php composer test` | Suite Pest. | `dtest` |
| `docker compose exec php composer audit --locked` | CVE des dépendances PHP. | — |
| `composer setup-hooks` | Active `.githooks/` — **une fois par clone**. | — |

## Front (thème)

| Commande | Effet | Alias |
| --- | --- | --- |
| `docker compose run --rm node npm ci` | Installe les dépendances. | `dnpm ci` |
| `docker compose run --rm node npm run build` | Build de production vers `dist/`. | `dnpm run build` |
| `docker compose run --rm node npm run dev` | Build de dev + watcher. | `dnpm run dev` |

## WP-CLI

`wp` cible `website/wordpress-core` grâce à `wp-cli.yml`. Le conteneur tourne en
root, d'où `--allow-root` (déjà inclus dans l'alias `dwp`).

| Commande | Effet |
| --- | --- |
| `dwp plugin list` | Liste les plugins et leur état. |
| `dwp search-replace 'ancien' 'nouveau' --all-tables` | Réécrit les URLs, y compris dans les données sérialisées. |
| `dwp cache flush` | Vide le cache objet. |
| `dwp rewrite flush` | Régénère les permaliens. |
| `dwp transient delete --expired` | Supprime les transients expirés. |
| `dwp db tables` / `dwp db size` | Inspecte la base. |

> ⚠️ `wp db query`, `wp db export` et `wp db import` **ne fonctionnent pas** ici :
> elles délèguent au client MySQL en ligne de commande, qui rejette le certificat
> auto-signé de MySQL 8.4. Utiliser `db-export` / `db-import` ci-dessous, ou
> phpMyAdmin. Détail et contournements : [`docker.md`](docker.md).

## Base de données

Ces alias passent par le client MySQL du conteneur `db` — le seul qui accepte le
certificat auto-signé de MySQL 8.4. **Ils remplacent `wp db query` / `wp db
export` / `wp db import`**, inopérantes depuis le conteneur `php`.

| Commande | Effet |
| --- | --- |
| `db-export dump.sql` | Exporte la base (`mysqldump --no-tablespaces`). |
| `db-import dump.sql` | Importe un dump. |
| `db-drop` | Supprime la base. |
| `db-create` | Recrée la base, vide, en utf8mb4. |
| `db-query "SELECT …"` | Exécute une requête ponctuelle. |
| `db-cli` | Ouvre un shell MySQL interactif. |

Cycle de remplacement complet :

```bash
db-export sauvegarde.sql        # filet avant manipulation
db-drop && db-create
db-import nouveau-dump.sql
```

> `db-drop` et `db-create` passent par `root` : l'utilisateur applicatif `lcds`
> n'a de privilèges que **sur** la base `lcds`, pas celui d'en créer une.

## Cache

| Commande | Effet |
| --- | --- |
| `dwp eval 'wp_cache_clear_cache();'` | Purge le cache pleine page (WP Super Cache). |
| `dwp eval 'lcds_cache_flush_all();'` | Purge tout le cache applicatif. |

## Dépendances

| Commande | Effet |
| --- | --- |
| `dcomposer outdated --direct` | Mises à jour disponibles sur les dépendances directes. |
| `dcomposer update <paquet>` | Met à jour un paquet précis. |

> Le cœur WordPress est le paquet `johnpbloch/wordpress`. Les plugins publics
> viennent de wpackagist (`wpackagist-plugin/*`). **ACF Pro n'est pas géré par
> Composer** — voir [`installation.md`](installation.md).

## Git

| Commande | Effet |
| --- | --- |
| `git commit --no-verify` | Committe en contournant le hook de pré-commit (ponctuel). |
