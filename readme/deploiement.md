# Déploiement

Deux environnements, sur le **même serveur**, déployés par GitHub Actions.

| Environnement | Déclencheur | Chemin distant |
| --- | --- | --- |
| Préprod | poussée sur `develop` (ou lancement manuel) | `$HOME/preprod-lcds` |
| Production | poussée d'un **tag** | `$HOME/prod-lcds` |

> L'entrée `remote_path` des workflows est **relative au home** de l'utilisateur
> SSH (`preprod-lcds`, pas `~/preprod-lcds`), et les scripts la préfixent par
> `$HOME`. Un `~` passé par une variable n'est **pas** développé par le shell :
> `"$ROOT/website"` resterait un chemin littéral commençant par `~`, et créerait
> un dossier nommé « ~ » dans le home au lieu de viser le bon endroit.

Les deux appellent le même workflow réutilisable
[`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml) ; les fichiers
`deploy-preprod.yml` et `deploy-prod.yml` ne portent que le déclencheur et le
chemin.

**Les contrôles passent avant l'envoi.** Chaque workflow de déploiement lance
d'abord `ci.yml` (Pint, PHPCS, PHPStan, Pest, CVE, gitleaks, build front) et
`codeql.yml`, et le job d'envoi les attend (`needs: [ci, codeql]`). Si l'un
échoue, le déploiement est **sauté** : le serveur n'est pas touché. Voir
[`ci-cd.md`](ci-cd.md).

## Prérequis côté hébergeur

À contrôler **avant** le premier déploiement sur un nouvel hébergement, puis
après toute montée de version de PHP :

```bash
php -r 'var_dump(gd_info()["WebP Support"]);'   # doit renvoyer bool(true)
php -v                                          # web ET cli : voir la note ci-dessous
```

**Le support WebP de GD n'est pas optionnel** : sans lui, `wp_get_attachment_image()`
sert des sous-tailles qui n'ont pas pu être encodées — voir [`images.md`](images.md).

Deux pièges déjà rencontrés :

- **La version de PHP en ligne de commande peut différer de celle du web.** Un
  écart met WP-CLI en échec côté serveur, `platform_check.php` de Composer
  refusant de charger l'autoloader.
- **`AllowOverride` peut être restreint.** Une directive refusée dans un
  `.htaccess` produit une 500 sur *toutes* les requêtes, sans message — voir
  [`securite.md`](securite.md).

> Sur un mutualisé OVH, le docroot se règle par domaine : *Hébergements >
> Multisite > Modifier > Dossier racine*. Y mettre `<remote_path>/website`, la
> structure de release décrite plus bas restant inchangée.

## Arborescence sur le serveur

```text
~/prod-lcds/
├── shared/              # JAMAIS écrasé : uniquement de l'état persistant
│   ├── .env             # configuration et secrets de l'environnement
│   ├── plugins/         # plugins sous licence (ACF Pro…)
│   ├── uploads/         # médias
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
| `website/.htaccess` | `shared/.htaccess` |
| `website/.htpasswd` | `shared/.htpasswd` *(si présent)* |
| `website/app/uploads` | `shared/uploads` |
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

## `config/` : livré par la release, au même niveau que `website/`

`config/` est du **code versionné**, pas de l'état persistant : il n'a rien à
faire dans `shared/`. La release le dépose à la racine du déploiement, exactement
comme il est placé en local, et `wp-config.php` le charge par
`dirname(__DIR__) . '/config'`. Aucun lien, aucune copie, aucune dérive possible.

La variation par environnement ne passe pas par ce dossier mais par le `.env` et
par `WP_ENV`, qui choisit le fichier de `config/environments/`.

> ⚠️ **Le docroot est passé explicitement** par `wp-config.php`
> (`$lcds_webroot_dir = __DIR__;`) plutôt que déduit dans `application.php` :
> `wp-config.php` est le seul fichier dont WordPress garantit l'emplacement, et
> une déduction casserait si `config/` venait à être atteint par un lien
> symbolique (PHP résout `__DIR__` vers le chemin réel).

> ⚠️ **`env()` dans un fichier de `config/environments/`** exige d'y ajouter
> `use function Env\env;` : l'import de `application.php` ne vaut que pour son
> propre fichier. Sans lui, « Call to undefined function env() » fait tomber
> l'environnement entier. Pint supprimant les imports inutilisés, il ne peut pas
> être posé d'avance — d'où l'avertissement en commentaire dans chaque fichier.

## `.htaccess` : le dépôt est la référence, `shared/` est ce qui est servi

C'est le seul cas où un fichier **versionné** n'est pas celui qu'Apache applique.
La raison : chaque environnement a besoin de ses propres directives — typiquement
la protection par mot de passe de la préprod, qui exige un `AuthUserFile` en
chemin **absolu**, donc impossible à écrire dans un fichier partagé.

Le workflow gère la bascule :

1. rsync livre le `website/.htaccess` du dépôt ;
2. si `shared/.htaccess` **n'existe pas**, il est **initialisé** depuis ce
   fichier — un nouvel environnement démarre donc avec tous les en-têtes de
   sécurité et la CSP ;
3. s'il existe et **diverge**, le diff est affiché dans le log du déploiement ;
4. `website/.htaccess` est remplacé par un lien vers `shared/.htaccess`.

> ⚠️ **Le point de vigilance.** Une fois `shared/.htaccess` créé, une
> modification des en-têtes de sécurité committée dans le dépôt **n'atteint plus
> le serveur toute seule**. L'étape 3 est là pour que ça ne passe pas inaperçu :
> **lire le log de déploiement** quand il signale une divergence, et reporter le
> changement à la main sur chaque environnement.

### Protéger la préprod par mot de passe

```bash
ssh user@serveur
htpasswd -c ~/preprod-lcds/shared/.htpasswd nom-utilisateur
```

Puis ajouter en tête de `~/preprod-lcds/shared/.htaccess` :

```apache
AuthType Basic
AuthName "Acces restreint"
AuthUserFile /home/USER/preprod-lcds/shared/.htpasswd
Require valid-user
```

Le chemin d'`AuthUserFile` doit être **absolu** — c'est précisément ce qui
interdit de mettre ce bloc dans le fichier versionné. Le lien
`website/.htpasswd` est recréé à chaque déploiement quand le fichier existe, et
supprimé sinon : la production reste ouverte tant qu'on n'y dépose pas de
`.htpasswd`.

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
2. `rsync -avzr --delete` avec exclusion de `/shared`, `/old_release`, `/website/.env`
   et des chemins qui sont des liens vers `shared/` ;
3. recréation des liens vers `shared/` ;
4. purge des caches si WP-CLI est présent sur le serveur.

## Secrets GitHub à configurer

*Settings > Secrets and variables > Actions > New repository secret.*

### Obligatoires

| Secret | Contenu |
| --- | --- |
| `DEPLOY_HOST` | Hôte ou IP du serveur (ex. `ssh.monhebergeur.fr`) |
| `DEPLOY_USER` | Utilisateur SSH |
| `DEPLOY_KEY` | **Clé privée** SSH complète, en-têtes compris (`-----BEGIN OPENSSH PRIVATE KEY-----` … `-----END OPENSSH PRIVATE KEY-----`), suivie d'un saut de ligne. La clé **publique** correspondante doit être dans le `~/.ssh/authorized_keys` du serveur. |

Ces trois suffisent à déployer. C'est le jeu utilisé par les autres projets sur
ce serveur : les mêmes valeurs sont réutilisables.

### Optionnels

Non définis, ils valent la chaîne vide et sont ignorés — aucun effet sur le
déploiement.

| Secret | Quand le renseigner |
| --- | --- |
| `DEPLOY_KEY_PASS` | La clé privée est protégée par une **passphrase**. Sans lui, le déploiement échoue sur `Load key: incorrect passphrase`. |
| `DEPLOY_FINGERPRINT` | Pour **vérifier l'identité du serveur** et fermer la porte à une attaque de l'homme du milieu. Empreinte SHA256 de la clé publique de l'hôte, obtenue par `ssh-keyscan <host> \| ssh-keygen -lf -`. |

> **Ce qui n'est PAS utilisé** : `KNOWN_HOSTS`, `PRIVATE_KEY`, `PASSPHRASE_KEY`.
> Ce sont des conventions d'autres projets. Ici la clé s'appelle `DEPLOY_KEY`,
> sa passphrase `DEPLOY_KEY_PASS`, et la vérification d'hôte passe par une
> empreinte (`DEPLOY_FINGERPRINT`) plutôt que par un `known_hosts` complet.
>
> La brique `rsync` ne sait pas exploiter une empreinte : sa propre vérification
> d'hôte (`strict_hostkeys_checking`) s'appuie sur un `known_hosts` et reste
> **désactivée**, en commentaire dans `deploy.yml`. Le trajet SSH est chiffré
> dans tous les cas ; seule l'authentification du serveur diffère.

### Environnements GitHub

Les jobs déclarent `environment: preprod` / `environment: production`. Créer ces
deux environnements dans *Settings > Environments* permet, si besoin :

- de **surcharger un secret par environnement** (un `DEPLOY_HOST` différent, par
  exemple) — le secret d'environnement l'emporte sur celui du dépôt ;
- d'exiger une **approbation manuelle** avant tout déploiement en production.

Sans les créer, les secrets du dépôt s'appliquent et tout fonctionne.

## Première mise en service d'un environnement

```bash
ssh user@serveur
mkdir -p ~/prod-lcds/shared/{plugins,uploads,cache}
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
ln -nsf "$PWD/shared/cache" website/app/cache
for p in shared/plugins/*/; do ln -nsf "$PWD/${p%/}" "website/app/plugins/$(basename "$p")"; done
```

## Points d'attention

- **Les exclusions rsync doivent commencer par `/`.** Sans slash initial, rsync
  exclut *tout* composant de chemin portant ce nom, à n'importe quelle
  profondeur : `--exclude=shared` avait ainsi supprimé
  `wp-includes/blocks/navigation-link/shared/` du cœur WordPress, avec un fatal
  à la clé. Le `/` ancre le motif à la racine du transfert.
- **OPcache.** Il n'est pas vidé par le déploiement. Sans rechargement de
  PHP-FPM, l'ancien code continue de tourner. La commande est en commentaire à
  la fin de `deploy.yml`, à activer avec celle de l'hébergeur.
- **`.htaccess` est versionné**, contrairement à code-cookie qui le prend dans
  `shared/`. Il porte les en-têtes de sécurité et la CSP : c'est le dépôt qui
  fait foi. Une modification faite directement sur le serveur (ou par un
  `wp rewrite flush --hard`) sera **écrasée au déploiement suivant**.
- **La base de données n'est jamais touchée** par le déploiement. Migration de
  contenu, `search-replace` et imports restent manuels.
