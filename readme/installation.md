# Installation

## Prérequis

| Outil     | Version |
| --------- | ------- |
| PHP       | 8.4+    |
| Composer  | 2       |
| Node      | 20      |
| npm       | 8+      |
| MySQL     | 5.7+ / MariaDB 10.4+ |
| Apache    | 2.4, avec `mod_rewrite`, `mod_headers`, `mod_deflate`, `mod_expires` |

## 1. Cloner et installer les dépendances PHP

```bash
git clone git@github.com:Rapkalin/LCDS.git
cd LCDS
composer install
```

Cela installe le cœur WordPress dans `website/wordpress-core/`, les plugins dans
`website/app/plugins/` et les dépendances dans `website/vendor/`. **Aucun de ces
dossiers n'est versionné.**

## 2. Activer le hook de pré-commit

À faire **une fois par clone** — le hook n'est pas activé automatiquement par Git :

```bash
composer setup-hooks
```

Il lance Pint, PHPCS et PHPStan avant chaque commit — voir
[`qualite-code.md`](qualite-code.md).

## 3. Configurer l'environnement

```bash
cp .env.example .env
```

Puis compléter :

- **`WP_ENV`** : `development` en local.
- **`WP_HOME`** : URL publique, **sans slash final** (`http://lcds.local`).
  `WP_SITEURL` en dérive automatiquement (`${WP_HOME}/wordpress-core`).
- **`DB_*`** : accès à la base locale.
- **Sels d'authentification** : générer un jeu complet sur
  <https://roots.io/salts.html> et remplacer les huit `generateme`.
  ⚠️ **Un jeu différent par environnement.** Des sels partagés entre la prod et
  un poste de dev rendent un cookie de session valable sur les deux.
- **`SMTP_*` / `MAIL_*`** : relais SMTP du formulaire de contact. Sans
  `SMTP_HOST`, WordPress retombe sur `mail()` de PHP — suffisant en local.

Le `.env` n'est **jamais** versionné, et le `.env` de production est géré côté
serveur.

## 4. Installer ACF Pro

ACF Pro est une **dépendance payante non versionnée** : elle n'est ni dans le
dépôt ni dans `composer.json`. Après `composer install`, l'installer à la main :

1. Télécharger la dernière version depuis <https://advancedcustomfields.com/my-account/> ;
2. Décompresser dans `website/app/plugins/advanced-custom-fields-pro/` ;
3. Activer le plugin et saisir la clé de licence dans l'admin.

> Sans ACF, le thème s'affiche mais tous les contenus éditoriaux sont vides :
> `get_field()` / `get_sub_field()` sont appelés dans presque tous les templates.

## 5. Configurer le vhost Apache

Le `DocumentRoot` pointe sur **`website/`**, pas sur la racine du dépôt.
`AllowOverride All` est **obligatoire** : sans lui, `website/.htaccess` est
ignoré et les en-têtes de sécurité ne sont pas posés.

```apache
<VirtualHost *:80>
  ServerName lcds.local
  DocumentRoot "/chemin/vers/LCDS/website"
  <Directory "/chemin/vers/LCDS/website">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
  </Directory>
</VirtualHost>
```

Puis ajouter `127.0.0.1 lcds.local` à `/etc/hosts`.

## 6. Installer le front

```bash
cd website/app/themes/lcds
nvm use            # Node 20, voir .nvmrc
npm install
npm run build      # production
npm run dev        # développement + watcher
```

`dist/` n'est pas versionné : le build doit être rejoué à chaque déploiement.

## 7. Vérifier

```bash
composer check     # Pint + PHPCS + PHPStan
composer test      # Pest
```
