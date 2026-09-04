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

À faire à la première configuration :

- renseigner le type d'organisation et le logo (données structurées locales —
  pertinent pour un cabinet, pensez au balisage `LocalBusiness` / `Dentist`) ;
- vérifier que le sitemap répond sur `/sitemap_index.xml` ;
- déclarer le sitemap dans la Search Console.

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

Les points qui pèsent sur le Core Web Vitals sont traités ailleurs :

- **Images WebP** : les sous-tailles générées sont encodées en WebP par le
  mu-plugin `lcds-webp`, servies par `srcset` — voir [`images.md`](images.md) ;
- **Cache navigateur et compression** : `mod_expires` / `mod_deflate`, voir
  [`securite.md`](securite.md) ;
- **Cache pleine page** : voir [`cache.md`](cache.md) ;
- **CSS/JS du cœur inutiles** : le thème déqueue `wp-block-library`,
  `global-styles`, `wp-emoji-styles` et déregistre jQuery (`inc/setup.php`).
