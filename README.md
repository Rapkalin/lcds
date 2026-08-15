# LCDS

Le site officiel de **La Clinique Du Sourire**.

![wordpress](https://img.shields.io/badge/wordpress-v7.0-0678BE.svg?style=flat-square)
![php](https://img.shields.io/badge/PHP-v8.4-828cb7.svg?style=flat-square)
![composer](https://img.shields.io/badge/composer-v2-126E75.svg?style=flat-square)
![Node](https://img.shields.io/badge/node-v20-644D31.svg?style=flat-square)
![webpack](https://img.shields.io/badge/webpack-v5-157B25.svg?style=flat-square)

## Démarrage rapide

```bash
git clone git@github.com:Rapkalin/LCDS.git && cd LCDS
composer install
composer setup-hooks           # active le hook de pré-commit
cp .env.example .env           # puis compléter DB_*, WP_HOME et les sels
cd website/app/themes/lcds && npm install && npm run build
```

Le `DocumentRoot` du vhost pointe sur **`website/`**, avec `AllowOverride All`.
Détail complet : [`readme/installation.md`](readme/installation.md).

## Documentation

| Sujet | Fichier |
| --- | --- |
| Installation, prérequis, vhost, ACF Pro | [`readme/installation.md`](readme/installation.md) |
| Arborescence et principes d'organisation | [`readme/structure.md`](readme/structure.md) |
| Durcissement, en-têtes HTTP, CSP, formulaire de contact | [`readme/securite.md`](readme/securite.md) |
| Cache applicatif, cache pleine page, OPcache | [`readme/cache.md`](readme/cache.md) |
| Yoast, indexation, SEO technique | [`readme/seo.md`](readme/seo.md) |
| Pint, PHPCS, PHPStan, Pest | [`readme/qualite-code.md`](readme/qualite-code.md) |
| Workflows GitHub Actions et déploiement | [`readme/ci-cd.md`](readme/ci-cd.md) |
| Aide-mémoire des commandes | [`readme/commandes.md`](readme/commandes.md) |

## Socle technique

- **Configuration par environnement** — `config/application.php` + `config/environments/`,
  alimentés par un `.env` à la racine. Aucun secret versionné.
- **Sécurité** — constantes de durcissement, en-têtes HTTP + CSP dans
  `website/.htaccess`, réduction de la surface exposée par le thème.
- **Cache** — cache applicatif à invalidation par versionnement (mu-plugin) et
  WP Super Cache pour le HTML complet (désactivé par défaut).
- **SEO** — Yoast SEO, plus blocage automatique de l'indexation hors production.
- **Qualité** — `composer check` (Pint + PHPCS/Slevomat + PHPStan niveau 6) et
  suite Pest, joués au pré-commit et en CI.
- **CI** — GitHub Actions : qualité, tests, CVE des dépendances, détection de
  secrets, SAST CodeQL, build front.

## Workflow Git

- Une branche par ticket : `feature/xxx`.
- `develop` = préprod, `main` = production, tag = livraison.
- `composer check` doit être au vert avant tout commit PHP.

```bash
git checkout develop && git merge origin/feature/xxx && git push
git checkout main && git merge origin/develop && git push
git tag -a x.x.x && git push --tags
```

> ⚠️ Aucun workflow de déploiement automatique n'existe à ce jour — voir
> [`readme/ci-cd.md`](readme/ci-cd.md).
