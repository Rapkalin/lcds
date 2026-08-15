# Commandes

## Qualité & tests

| Commande | Effet |
| --- | --- |
| `composer check` | Enchaîne lint + types + stan. **Gate du pré-commit et de la CI.** |
| `composer lint` | Style PHP (Pint), vérifie sans modifier. |
| `composer lint:fix` | Style PHP, corrige automatiquement. |
| `composer types` | Déclaration native des types (PHPCS/Slevomat). |
| `composer types:fix` | Ajoute les types déductibles. |
| `composer stan` | Analyse statique PHPStan (niveau 6). |
| `composer test` | Suite Pest. |
| `composer audit --locked` | CVE des dépendances PHP. |
| `composer setup-hooks` | Active `.githooks/` — **à faire une fois par clone**. |

## Front (thème)

Depuis `website/app/themes/lcds/` :

| Commande | Effet |
| --- | --- |
| `npm install` | Installe les dépendances (Node 20, voir `.nvmrc`). |
| `npm run dev` | Build de développement + watcher. |
| `npm run build` | Build de production (minifié) vers `dist/`. |

## Dépendances

| Commande | Effet |
| --- | --- |
| `composer outdated --direct` | Mises à jour disponibles sur les dépendances directes. |
| `composer update <paquet>` | Met à jour un paquet précis. |
| `composer update` | Met tout à jour dans les bornes de `composer.json`. |

> Le cœur WordPress est le paquet `johnpbloch/wordpress`. Les plugins publics
> viennent de wpackagist (`wpackagist-plugin/*`). **ACF Pro n'est pas géré par
> Composer** — voir [`installation.md`](installation.md).

## Cache

| Commande | Effet |
| --- | --- |
| `wp eval 'wp_cache_clear_cache();'` | Purge le cache pleine page (WP Super Cache). |
| `wp eval 'lcds_cache_flush_all();'` | Purge tout le cache applicatif. |
| `wp transient delete --expired` | Supprime les transients expirés de `wp_options`. |

## Git

| Commande | Effet |
| --- | --- |
| `git commit --no-verify` | Committe en contournant le hook de pré-commit (ponctuel). |
