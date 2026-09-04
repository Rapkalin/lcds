# LCDS

Le site officiel de **La Clinique Du Sourire**.

![wordpress](https://img.shields.io/badge/wordpress-v7.0-0678BE.svg?style=flat-square)
![php](https://img.shields.io/badge/PHP-v8.4-828cb7.svg?style=flat-square)
![composer](https://img.shields.io/badge/composer-v2-126E75.svg?style=flat-square)
![Node](https://img.shields.io/badge/node-v20-644D31.svg?style=flat-square)
![webpack](https://img.shields.io/badge/webpack-v5-157B25.svg?style=flat-square)

## Démarrage rapide

Seul prérequis : **Docker**. PHP, Composer, MySQL, WP-CLI et Node sont dans les
conteneurs.

```bash
git clone git@github.com:Rapkalin/LCDS.git && cd LCDS
mkdir -p shared/plugins
cp .env.example shared/.env
ln -nsf shared/.env .env       # docker compose lit le .env à la racine
docker compose up -d
docker compose run --rm node npm ci && docker compose run --rm node npm run build
composer setup-hooks           # active le hook de pré-commit
```

| Service | URL | Accès |
| --- | --- | --- |
| Front | <http://localhost:8020> | — |
| Admin | <http://localhost:8020/wordpress-core/wp-admin> | `admin` / `admin` |
| phpMyAdmin | <http://localhost:8021> | `lcds` / `lcds` |
| Mailpit (mails capturés) | <http://localhost:8025> | — |

Les plugins gratuits arrivent par Composer ; les plugins **payants** se déposent
dans `shared/plugins/` et sont reliés automatiquement (même mécanisme en local et
sur le serveur) — voir [`readme/installation.md`](readme/installation.md).

## Documentation

| Sujet | Fichier |
| --- | --- |
| Installation, ACF Pro, import d'une base | [`readme/installation.md`](readme/installation.md) |
| Environnement Docker, services, dépannage | [`readme/docker.md`](readme/docker.md) |
| Arborescence et principes d'organisation | [`readme/structure.md`](readme/structure.md) |
| Durcissement, en-têtes HTTP, CSP, formulaire de contact | [`readme/securite.md`](readme/securite.md) |
| Cache applicatif, cache pleine page, OPcache | [`readme/cache.md`](readme/cache.md) |
| Tokens, grille, polices, invalidation des assets | [`readme/front.md`](readme/front.md) |
| Images : conversion WebP et helper d'affichage | [`readme/images.md`](readme/images.md) |
| Relevés Figma : protocole et cache des maquettes | [`design/figma/README.md`](design/figma/README.md) |
| Contribution : blocs, champs, page d'accueil | [`readme/contribution.md`](readme/contribution.md) |
| Menus : emplacements et création automatique | [`readme/menus.md`](readme/menus.md) |
| Yoast, indexation, SEO technique | [`readme/seo.md`](readme/seo.md) |
| Pint, PHPCS, PHPStan, Pest | [`readme/qualite-code.md`](readme/qualite-code.md) |
| QA : portes à passer, campagne front | [`readme/qa.md`](readme/qa.md) |
| Workflows GitHub Actions | [`readme/ci-cd.md`](readme/ci-cd.md) |
| Déploiement, `shared/`, plugins payants, rollback | [`readme/deploiement.md`](readme/deploiement.md) |
| Aide-mémoire des commandes | [`readme/commandes.md`](readme/commandes.md) |

## Socle technique

- **Environnement Docker** — Apache + PHP 8.4, MySQL 8.4, phpMyAdmin, Mailpit et un
  conteneur Node pour le build du thème. Projet bind-monté, initialisation
  idempotente au démarrage.
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
  secrets, SAST CodeQL, build front. **Aucun déploiement ne part si l'un de ces
  contrôles échoue.**

## Workflow Git

- Une branche par ticket : `feature/xxx`.
- `docker compose exec php composer check` doit être au vert avant tout commit PHP.

```bash
# Pousser sur develop déploie automatiquement la PRÉPROD
git checkout develop && git merge origin/feature/xxx && git push

# Puis, une fois validé, pousser un tag déploie la PRODUCTION
git checkout main && git merge origin/develop && git push
git tag -a x.x.x -m "…" && git push --tags
```

Détail du déploiement, arborescence serveur et rollback :
[`readme/deploiement.md`](readme/deploiement.md).
