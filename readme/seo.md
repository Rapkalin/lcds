# SEO

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

- **Images WebP/AVIF** : `webp-converter-for-media` sert automatiquement des
  formats modernes via des règles de réécriture (bloc « Converter for Media »
  dans `website/.htaccess`) ;
- **Cache navigateur et compression** : `mod_expires` / `mod_deflate`, voir
  [`securite.md`](securite.md) ;
- **Cache pleine page** : voir [`cache.md`](cache.md) ;
- **CSS/JS du cœur inutiles** : le thème déqueue `wp-block-library`,
  `global-styles`, `wp-emoji-styles` et déregistre jQuery (`inc/setup.php`).
