# Structure du projet

```text
lcds/
├── .github/
│   └── workflows/
│       ├── ci.yml              # Qualité PHP, tests, CVE, secrets, build front
│       └── codeql.yml          # SAST CodeQL (JavaScript uniquement)
├── .githooks/
│   └── pre-commit              # Pint + PHPCS + PHPStan avant chaque commit
├── bin/
│   └── init.sh                 # Init WordPress idempotente (jouée au démarrage)
├── docker/
│   ├── apache/000-default.conf # Vhost du conteneur (AllowOverride All)
│   └── php/
│       ├── Dockerfile          # PHP 8.4 + Apache + WP-CLI + Composer
│       └── entrypoint.sh       # Dépendances -> .env -> base -> init -> apache
├── config/
│   ├── application.php         # Configuration de base (lit le .env racine)
│   └── environments/           # Surcharges par environnement
│       ├── development.php
│       ├── staging.php
│       └── production.php
├── readme/                     # Documentation détaillée (ce dossier)
├── shared/                     # NON versionné, jamais écrasé par un déploiement
│   ├── .env                    # Configuration et secrets de l'environnement
│   ├── plugins/                # Plugins sous licence (ACF Pro…)
│   └── uploads/, cache/        # En production uniquement
├── tests/                      # Suite Pest (hors WordPress)
├── website/                    # DOCROOT — c'est ici que pointe le vhost Apache
│   ├── .htaccess               # En-têtes de sécurité, CSP, gzip, cache assets
│   ├── index.php               # Front controller
│   ├── wp-config.php           # 3 lignes : autoload + config/ + wp-settings
│   ├── wordpress-core/         # Cœur WordPress (Composer, non versionné)
│   ├── vendor/                 # Dépendances Composer (non versionné)
│   └── app/                    # Contenu (remplace wp-content)
│       ├── mu-plugins/
│       │   ├── bedrock-autoloader.php   # Charge les mu-plugins en sous-dossier
│       │   ├── lcds-cache.php           # Cache applicatif (point d'entrée)
│       │   ├── enums/                   # LcdsCacheKey, LcdsCacheGroup
│       │   └── cache/                   # Moteur + invalidation
│       ├── plugins/            # Installés par Composer / ACF Pro (non versionné)
│       ├── themes/lcds/        # Thème du projet
│       ├── cache/              # Cache pleine page WP Super Cache
│       ├── languages/          # Traductions
│       └── uploads/            # Médias (non versionnés)
├── .env                        # Lien vers shared/.env (pour Docker Compose)
├── .env.example                # Modèle de configuration
├── .editorconfig
├── aliases.sh                  # Raccourcis Docker (source aliases.sh)
├── docker-compose.yml          # php / db / phpmyadmin / mailpit / node
├── composer.json               # Dépendances PHP & scripts (lint, stan, test)
├── phpcs.xml                   # Déclaration native des types
├── phpstan.neon                # Analyse statique niveau 6
├── phpunit.xml.dist
├── pint.json                   # Style PER
└── CHANGELOG.md
```

## Principes

**Le docroot est `website/`, pas la racine du dépôt.** Tout ce qui n'a pas à être
servi par Apache — `.env`, `composer.json`, `config/`, `tests/`, `readme/` — vit
au-dessus. C'est la première ligne de défense : ces fichiers ne sont pas
atteignables par HTTP, même si PHP cesse de s'exécuter.

**Le cœur WordPress est une dépendance.** `website/wordpress-core/` est installé
par Composer et n'est pas versionné. On ne modifie jamais son contenu : une mise
à jour l'écraserait.

**`wp-config.php` ne contient aucune valeur.** Il charge l'autoloader puis
`config/application.php`, qui lit `shared/.env` (ou le `.env` racine en repli).
Les secrets ne sont donc jamais dans un fichier versionné, et la configuration
diffère par environnement sans duplication — voir
[`installation.md`](installation.md).

**Une release est jetable, `shared/` ne l'est pas.** Le déploiement écrase
`website/` et `config/` sans réfléchir ; tout ce qui doit survivre — `.env`,
plugins sous licence, médias, cache — vit dans `shared/` et est relié par des
liens symboliques. Voir [`deploiement.md`](deploiement.md).

**Le cache applicatif est un mu-plugin, pas du code de thème.** Il doit rester
disponible pour WP-CLI, le cron et l'admin, et survivre à un changement de
thème — voir [`cache.md`](cache.md).

**Le projet est bind-monté dans le conteneur, pas copié dans l'image.** Le code
édité sur l'hôte est servi immédiatement et `website/vendor` reste visible par
l'IDE et le hook de pré-commit — voir [`docker.md`](docker.md).
