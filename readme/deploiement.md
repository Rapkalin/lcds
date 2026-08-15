# Déploiement

Deux environnements, sur le **même serveur**, déployés par GitHub Actions.

| Environnement | Déclencheur | Chemin distant |
| --- | --- | --- |
| Préprod | poussée sur `develop` (ou lancement manuel) | `~/preprod-lcds` |
| Production | poussée d'un **tag** | `~/prod-lcds` |

Les deux appellent le même workflow réutilisable
[`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml) ; les fichiers
`deploy-preprod.yml` et `deploy-prod.yml` ne portent que le déclencheur et le
chemin.

## Arborescence sur le serveur

```text
~/prod-lcds/
├── shared/              # JAMAIS écrasé par un déploiement
│   ├── .env             # configuration et secrets de l'environnement
│   ├── plugins/         # plugins sous licence (ACF Pro…)
│   ├── uploads/         # médias
│   ├── uploads-webpc/   # dérivés WebP/AVIF
│   └── cache/           # cache pleine page
├── config/              # release
├── website/             # release — DocumentRoot du vhost
├── wp-cli.yml           # release
└── old_release/         # copie de la release précédente (rollback)
```

Le `DocumentRoot` du vhost pointe sur `~/prod-lcds/website`, avec
`AllowOverride All` — sans quoi les en-têtes de sécurité de `website/.htaccess`
ne sont pas posés (voir [`securite.md`](securite.md)).

## Le principe : `shared/` survit aux releases

Une release est **jetable** : elle est effacée et remplacée à chaque
déploiement. Tout ce qui ne doit pas disparaître vit dans `shared/`, et la
release y est reliée par des liens symboliques recréés après chaque rsync.

| Chemin dans la release | Pointe vers |
| --- | --- |
| `.env` | `shared/.env` |
| `website/app/uploads` | `shared/uploads` |
| `website/app/uploads-webpc` | `shared/uploads-webpc` |
| `website/app/cache` | `shared/cache` |
| `website/app/plugins/<plugin-payant>` | `shared/plugins/<plugin-payant>` |

C'est ce qui permet de ne **jamais** versionner un secret ni un plugin sous
licence, tout en gardant un déploiement qui écrase tout le reste sans réfléchir.

## Les plugins : deux origines

- **Gratuits** → déclarés dans `composer.json` (wpackagist) et **téléchargés
  pendant le build** de la CI. Ils font partie de la release, ne sont pas
  versionnés, et se mettent à jour en changeant une contrainte de version.
- **Payants** → déposés **à la main** dans `shared/plugins/` sur le serveur. Le
  workflow les relie dans `website/app/plugins/` après chaque déploiement, via
  une boucle : **ajouter un plugin sous licence ne demande aucune modification
  du workflow**, il suffit de le déposer dans `shared/plugins/`.

```bash
# Installer ou mettre à jour un plugin payant en production
scp -r advanced-custom-fields-pro user@serveur:~/prod-lcds/shared/plugins/
```

Le lien est recréé au déploiement suivant ; pour le créer immédiatement :

```bash
ln -nsf ~/prod-lcds/shared/plugins/advanced-custom-fields-pro \
        ~/prod-lcds/website/app/plugins/advanced-custom-fields-pro
```

En local, le même mécanisme tourne : `bin/init.sh` relie tout ce qui est dans
`shared/plugins/` à chaque démarrage du conteneur.

## Ce que fait le workflow

**Job `build`**

1. `composer install --no-dev --optimize-autoloader` — cœur WordPress, vendor et
   plugins gratuits ;
2. `npm ci && npm run build` dans le thème — **indispensable**, `dist/` n'est pas
   versionné et le site partirait sans CSS ni JS ;
3. suppression de `node_modules` (il n'a rien à faire sur le serveur) ;
4. assemblage de `website/` + `config/` + `wp-cli.yml` dans une archive.

**Job `deploy`**

1. copie de la release en place vers `old_release/` (`cp -a` : le site reste
   servi pendant la copie) ;
2. `rsync -avzr --delete` avec exclusion de `shared`, `old_release`, `.env` et
   des chemins qui sont des liens vers `shared/` ;
3. recréation des liens vers `shared/` ;
4. purge des caches si WP-CLI est présent sur le serveur.

## Secrets GitHub à configurer

*Settings > Secrets and variables > Actions* — ce sont les mêmes que pour les
autres projets déployés sur ce serveur :

| Secret | Rôle |
| --- | --- |
| `DEPLOY_HOST` | Hôte / IP du serveur |
| `DEPLOY_USER` | Utilisateur SSH |
| `DEPLOY_KEY` | Clé privée SSH |

## Première mise en service d'un environnement

```bash
ssh user@serveur
mkdir -p ~/prod-lcds/shared/{plugins,uploads,uploads-webpc,cache}
```

Puis créer `~/prod-lcds/shared/.env` à partir de
[`.env.example`](../.env.example), en veillant à :

- `WP_ENV='production'` (ou `staging` en préprod) ;
- `WP_HOME` = URL réelle du site, **sans slash final** ;
- des **sels uniques**, générés sur <https://roots.io/salts.html> — jamais ceux
  d'un autre environnement ;
- les accès base de données de l'hébergeur ;
- `DISABLE_WP_CRON='true'` en production, **et** un cron système qui appelle
  `wp-cron.php` (sinon la purge des transients ne tourne plus, voir
  [`cache.md`](cache.md)).

Déposer ensuite les plugins payants dans `shared/plugins/`, et lancer le premier
déploiement.

## Rollback

`old_release/` contient la release précédente :

```bash
ssh user@serveur
cd ~/prod-lcds
rm -rf website config
cp -a old_release/website old_release/config .
ln -nsf "$PWD/shared/.env" .env
ln -nsf "$PWD/shared/uploads" website/app/uploads
ln -nsf "$PWD/shared/uploads-webpc" website/app/uploads-webpc
ln -nsf "$PWD/shared/cache" website/app/cache
for p in shared/plugins/*/; do ln -nsf "$PWD/${p%/}" "website/app/plugins/$(basename "$p")"; done
```

## Points d'attention

- **OPcache.** Il n'est pas vidé par le déploiement. Sans rechargement de
  PHP-FPM, l'ancien code continue de tourner. La commande est en commentaire à
  la fin de `deploy.yml`, à activer avec celle de l'hébergeur.
- **`.htaccess` est versionné**, contrairement à code-cookie qui le prend dans
  `shared/`. Il porte les en-têtes de sécurité et la CSP : c'est le dépôt qui
  fait foi. Une modification faite directement sur le serveur (ou par un
  `wp rewrite flush --hard`) sera **écrasée au déploiement suivant**.
- **La base de données n'est jamais touchée** par le déploiement. Migration de
  contenu, `search-replace` et imports restent manuels.
