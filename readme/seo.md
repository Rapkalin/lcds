# SEO

## Les gabarits de titre de Yoast, et leur piège de langue

Yoast écrit ses gabarits de titre dans l'option `wpseo_titles` **au moment de
son activation**. Activé avant l'installation de son paquet de langue, il y range
les chaînes anglaises — et installer la traduction ensuite **ne réécrit rien**.

Constaté sur ce projet : `Page not found - La Clinique du Sourire`,
`You searched for … `, plus quatre libellés de fil d'Ariane, sur un site dont
`<html lang="fr">` et la locale valent bien `fr_FR`.

Deux parades, et les deux sont en place :

1. **Prévention** — `bin/init.sh` installe `wp language plugin install --all
   fr_FR` **avant** d'activer les plugins. L'ordre est commenté sur place ;
   l'inverser reproduit le défaut.
2. **Rattrapage** — `lcds_reset_seo_titles()` (`inc/seo.php`), jouée par
   `bin/init.sh` et par le workflow de déploiement.

Le rattrapage est **non destructif**, et ce n'est pas une promesse : il bascule
la locale en `en_US`, demande à Yoast ses défauts anglais, et ne retire que les
clés dont la valeur enregistrée est *exactement* ce défaut anglais. Un gabarit
saisi par un contributeur ne peut pas être touché — vérifié en posant un titre
personnalisé, qui survit. Une clé retirée est recalculée par Yoast, cette fois
traduite. Idempotent.

Elle vit dans le **thème** et non dans `bin/` : l'artefact de déploiement ne
contient que `website/`, `config/` et `wp-cli.yml`, un script de `bin/` serait
introuvable sur le serveur.

`bin/qa-front.sh` échoue si un gabarit repasse en anglais.

## Yoast SEO

Le plugin **Yoast SEO** (`wpackagist-plugin/wordpress-seo`) est installé par
Composer. Il porte les titres, méta-descriptions, balises Open Graph, données
structurées et le sitemap XML.

Après `composer install`, l'activer une fois dans l'admin. En production
`DISALLOW_FILE_MODS=true` empêche l'installation depuis l'admin, mais **pas**
l'activation d'un plugin déjà présent sur le disque.

À faire à la première configuration **de chaque environnement** (ces réglages
vivent en base, aucun déploiement ne les porte) :

1. *Yoast SEO → Réglages → Représentation du site* : **Organisation**, nom
   « La Clinique du Sourire », et un **logo en PNG** (512 × 512 conseillé).
   ⚠️ **Sans logo, Yoast ne publie AUCUN bloc `Organization`** — et donc pas
   non plus la fiche `Dentist` ci-dessous (`generate_site_represents()` de
   Yoast refuse une organisation sans logo). Le logo du thème est un SVG en
   ligne, que Google n'accepte pas comme logo : il faut un fichier ;
2. *Yoast SEO → Réglages → llms.txt* : activer — voir plus bas ;
3. *Réglages → Configuration → Fiche de l'établissement* : adresse, téléphone,
   horaires — voir plus bas ;
4. renseigner la **méta-description** de chaque page (boîte Yoast, sous le
   contenu). Aucune n'en a au 08/11/2026, et Lighthouse le signale ;
5. vérifier qu'il ne reste **aucun contenu par défaut de WordPress** publié
   (« Sample Page », « Hello world! ») : le thème masque le menu *Articles*,
   on ne les voit donc pas dans l'admin, mais ils sont publiés, indexables et
   repris par le `llms.txt` ;
6. vérifier que le sitemap répond sur `/sitemap_index.xml`, puis le déclarer
   dans la Search Console.

## Fiche `Dentist` (données structurées locales)

Yoast décrit le site comme une `Organization`. Le thème la complète
(`wpseo_schema_organization`, `inc/seo.php`) en **`Dentist`** — sous-type de
`LocalBusiness` — avec l'adresse postale, le téléphone et les horaires saisis
dans *Réglages → Configuration → Fiche de l'établissement*. Ces champs ne
s'affichent nulle part sur le site : ils servent aux moteurs de recherche et
aux assistants conversationnels (GEO).

- **Sans rue ni ville, rien n'est ajouté** : Google rejette un `LocalBusiness`
  sans adresse. L'organisation reste alors une `Organization` simple.
- Une plage horaire incomplète ou mal formée est **écartée** plutôt que
  publiée : une seule heure invalide invalide toute la spécification.
- Le contrat est tenu par `tests/Unit/BusinessSchemaTest.php`.

> ⚠️ L'adresse existe aussi, en texte libre, dans le pied de page (champ
> « Adresse »). Les deux sont saisies séparément : un déménagement se reporte
> aux deux endroits.

Contrôle : coller une URL de page dans le [test des résultats
enrichis](https://search.google.com/test/rich-results) une fois en production.

## `llms.txt`

Généré par **Yoast** (réglage *llms.txt*), à la racine du site, puis réécrit
chaque semaine par une tâche cron. Deux réglages du projet le rendent viable :

- **le chemin est forcé sur le docroot** (`lcds_llms_txt_path()`,
  `inc/seo.php`) : Yoast le déduit de `get_home_path()`, qui vaut `/` sous
  WP-CLI — un cron système passant par WP-CLI n'aurait jamais écrit le fichier ;
- **le déploiement l'exclut** (`--exclude=/website/llms.txt` dans
  `deploy.yml`) : sinon `rsync --delete` l'effaçait à chaque livraison, jusqu'à
  la régénération suivante. Il est aussi ignoré par Git.

Il ne liste que des titres et des URL tant que les pages n'ont pas de
méta-description.

> Le thème appelle `add_theme_support('title-tag')` et filtre
> `document_title_separator` : Yoast reprend la main sur le `<title>` dès qu'un
> titre SEO est renseigné sur le contenu.

## Blocage de l'indexation hors production

`roots/bedrock-disallow-indexing` est installé en mu-plugin. Il envoie un
`X-Robots-Tag: noindex` et force `blog_public = 0` dès que la constante
`DISALLOW_INDEXING` vaut `true` — ce qui est le cas en `development` et en
`staging` (`config/environments/`).

**En production la constante n'est pas définie, donc le site est indexable.**
C'est le comportement voulu : la préprod ne doit jamais apparaître dans les
résultats de recherche, la prod si.

> ⚠️ Le réglage « Visibilité pour les moteurs de recherche » de l'admin
> WordPress est **surchargé** par ce mu-plugin hors production. Ne cherchez pas à
> le décocher sur la préprod, changez l'environnement.

## Performance et SEO technique

Mesuré le 08/11/2026 avec Lighthouse 12.8 en local (mobile, réseau simulé),
avant → après la 4.1.0 :

| Page | Performance | LCP | Poids |
| --- | --- | --- | --- |
| Accueil | 80 → 96 | 4,3 s → 2,7 s | 624 → 433 Ko |
| Le cabinet | 91 → 99 | 3,5 s → 2,0 s | 529 → 297 Ko |
| L'équipe | 88 → 98 | 3,5 s → 2,3 s | 531 → 299 Ko |
| Conseils | 90 → 99 | 2,7 s → 1,7 s | 353 → 121 Ko |

Ce qui a compté, dans l'ordre :

- **Le visuel du pied de page était le LCP de TOUTES les pages.** Fixé au bas
  de la fenêtre derrière le contenu, il était téléchargé d'emblée et compté
  comme plus grand élément peint, alors que personne ne le voit avant la fin
  du défilement. Il n'est plus affiché qu'à l'approche du pied de page — voir
  `assets/styles/partials/footer.scss`. Deux assertions de `bin/qa-front.sh`
  le gardent ;
- **Inter réduite au latin** : 271 → 85 Ko de polices, préchargées — voir
  [`front.md`](front.md) ;
- **`main.js` n'était pas compressé** : Apache le sert en `text/javascript`,
  absent de la liste `DEFLATE`. 16 → 5 Ko ;
- **cache navigateur d'un an `immutable`** sur les assets statiques, tous
  versionnés — voir [`front.md`](front.md), « Invalidation du cache des
  assets ».

Restent signalés et assumés : le contraste blanc sur orange du bouton
« Prendre RDV » (voir [`accessibilite.md`](accessibilite.md)), la feuille
`main.css` bloquante (8 Ko compressés, l'inliner coûterait plus qu'elle).

Autres points traités ailleurs :

- **Images WebP** : les sous-tailles générées sont encodées en WebP par le
  mu-plugin `lcds-webp`, servies par `srcset` — voir [`images.md`](images.md) ;
- **Compression et cache navigateur** : `mod_deflate` / `mod_expires` dans
  `website/.htaccess`. ⚠️ Le serveur applique `shared/.htaccess` : toute
  modification s'y reporte à la main — voir [`deploiement.md`](deploiement.md) ;
- **Cache applicatif et requêtes** : voir [`cache.md`](cache.md) ;
- **CSS/JS du cœur inutiles** : le thème déqueue `wp-block-library`,
  `global-styles`, `wp-emoji-styles` et déregistre jQuery (`inc/setup.php`).
