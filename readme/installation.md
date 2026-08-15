# Installation

Le projet tourne sur **Docker**. Un seul prérequis sur la machine : Docker
Desktop (ou Docker Engine + Compose v2). PHP, Composer, MySQL, WP-CLI et Node
vivent dans les conteneurs.

## 1. Cloner et démarrer

```bash
git clone git@github.com:Rapkalin/LCDS.git && cd LCDS
mkdir -p shared/plugins
cp .env.example shared/.env
ln -nsf shared/.env .env      # docker compose lit le .env à la racine
docker compose up -d
```

> **Pourquoi `shared/` ?** Ce dossier porte tout ce qui ne doit **jamais** être
> écrasé par un déploiement ni versionné : le `.env`, les plugins sous licence
> et, en production, les médias et le cache. Le lien `.env → shared/.env`
> n'existe que pour Docker Compose, qui ne sait lire que la racine ; PHP, lui,
> lit `shared/.env` directement. Détail : [`deploiement.md`](deploiement.md).

Au premier démarrage, l'entrypoint enchaîne : `composer install` → génération des
sels → attente de la base → `bin/init.sh` (installation WordPress, activation du
thème et des plugins, langue fr_FR) → Apache. Suivre le déroulé :

```bash
docker compose logs -f php
```

| Service | URL | Accès |
| --- | --- | --- |
| Front | <http://localhost:8020> | — |
| Admin | <http://localhost:8020/wordpress-core/wp-admin> | `admin` / `admin` |
| phpMyAdmin | <http://localhost:8021> | `lcds` / `lcds` |
| Mailpit | <http://localhost:8025> | — |

Les ports se changent dans le `.env` (`APP_PORT`, `PMA_PORT`, `MAILPIT_PORT`,
`DB_PORT`). Si un port est déjà pris sur la machine (MAMP occupe souvent 80 et
3306), c'est là qu'on l'ajuste.

## 2. Construire le front

Le thème est compilé par webpack et `dist/` n'est pas versionné :

```bash
docker compose run --rm node npm ci        # première fois
docker compose run --rm node npm run build # production
docker compose run --rm node npm run dev   # développement + watcher
```

> `npm ci` réinstalle `node_modules` pour Linux. Si tu avais déjà lancé `npm` sur
> l'hôte, c'est normal et attendu — le build passe désormais par le conteneur.

## 3. Installer les plugins payants (ACF Pro)

Les plugins **gratuits** sont déclarés dans `composer.json` et installés
automatiquement. Les plugins **payants** ne peuvent pas l'être (licence) : ils se
déposent dans `shared/plugins/`, et `bin/init.sh` les relie dans
`website/app/plugins/` à chaque démarrage — exactement comme le fait le
déploiement sur le serveur.

Sans ACF Pro, le thème s'affiche mais tous les contenus éditoriaux sont vides
(`get_field()` / `get_sub_field()` sont appelés dans presque tous les templates).

1. Télécharger la dernière version sur <https://advancedcustomfields.com/my-account/> ;
2. Décompresser dans **`shared/plugins/advanced-custom-fields-pro/`** ;
3. `docker compose restart php` — le lien est créé et le plugin activé ;
4. Saisir la clé de licence dans l'admin.

Tout nouveau plugin sous licence suit le même chemin : le déposer dans
`shared/plugins/`, rien d'autre à configurer.

## 4. Importer une base existante

Le premier démarrage crée un site **vide**. Pour repartir d'une base existante :

```bash
source aliases.sh

# 1. Exporter depuis l'ancienne base (hors Docker). Les identifiants d'avant
#    la bascule sont conservés en commentaire « # LEGACY » dans le .env.
mysqldump -h localhost -u <user> -p <base> > dump.sql

# 2. Réinitialiser puis importer dans le conteneur
db-drop && db-create
db-import dump.sql

# 3. Réécrire les URLs (l'ancienne base contient http://lcds.local)
dwp search-replace 'http://lcds.local' 'http://localhost:8020' --all-tables
dwp cache flush
```

> ⚠️ **`search-replace` est indispensable.** Une base qui contient encore
> l'ancien domaine renvoie des redirections en boucle et des assets introuvables.
> Utiliser `wp search-replace` et non un `sed` sur le dump : les URLs sont aussi
> présentes dans des données PHP sérialisées, dont la longueur est encodée.

Pour repartir totalement de zéro : `docker compose down -v` (⚠️ supprime la base
**et** les médias) puis `docker compose up -d`.

## 5. Activer le hook de pré-commit

À faire **une fois par clone** — Git n'active pas les hooks automatiquement :

```bash
composer setup-hooks
```

Il lance Pint, PHPCS et PHPStan avant chaque commit, **dans le conteneur** quand
il tourne (même version de PHP que la CI), sinon sur les binaires de l'hôte.
Voir [`qualite-code.md`](qualite-code.md).

## 6. Vérifier

```bash
docker compose exec php composer check   # Pint + PHPCS + PHPStan
docker compose exec php composer test    # Pest
```

Ou, après `source aliases.sh` : `dcheck` et `dtest`.

## Mails

Aucun mail ne sort de la machine : le `.env` pointe `SMTP_HOST` sur le conteneur
**Mailpit**, qui capture tout et l'affiche sur <http://localhost:8025>. C'est ce
qui permet de tester le formulaire de contact sans risquer d'écrire à un vrai
destinataire. Pour tester un relais réel, réactiver les lignes `# LEGACY`
correspondantes du `.env`.

## Sans Docker (non recommandé)

Le projet reste utilisable sur un Apache natif : `DocumentRoot` sur `website/`,
`AllowOverride All`, PHP 8.4, et `DB_HOST='localhost'` dans le `.env`. Aucune
partie du code ne dépend de Docker. C'est simplement une configuration de plus à
maintenir à la main.
