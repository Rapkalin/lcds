# Journal des modifications

Une section par version. La version fait foi dans **`composer.json`** — c'est
elle que le pied de page du site affiche, via `lcds_site_version()`.

> **À tenir à jour à chaque livraison.** Voir la règle 8 de
> [`CLAUDE.md`](CLAUDE.md) : une version qui bouge sans entrée ici rend le
> journal inutile, et une entrée sans version rend la version fausse.

## 1.1.0

Première version qui expose sa propre version.

### Ajouté

- **Version du site dans le pied de page**, lue dans `composer.json` — source
  unique. `composer.json` part désormais avec l'artefact de déploiement : il
  vit hors du docroot, donc Apache ne le sert pas.
- **Contribution de la page d'accueil en blocs de l'éditeur** : six sections
  déclarées comme blocs ACF (`acf/lcds-*`), champs versionnés en JSON local,
  titre `h1` contribuable — voir [`readme/contribution.md`](readme/contribution.md).
- **Sections de la page d'accueil** : en-tête, hero, texte et galerie,
  accordéon des traitements, parcours de soin défilant, carrousel de cartes
  inclinées, informations pratiques.
- **Conversion WebP native** via `image_editor_output_format`, sans plugin —
  voir [`readme/images.md`](readme/images.md).
- **Menus versionnés** : emplacements, rattachement et **entrées par défaut**
  amorcés par le code, sur tous les environnements. Sans ça un déploiement neuf
  sortait un en-tête sans navigation.
- **Campagne de QA du front** : assertions jouées dans un navigateur sans
  interface, à 1440, 500 et **320px** — voir [`readme/qa.md`](readme/qa.md).
- **Page d'accessibilité** documentant les décisions et les contrastes mesurés
  — voir [`readme/accessibilite.md`](readme/accessibilite.md).

### Corrigé

- **Accessibilité** : les 16 images de la page d'accueil sortaient en `alt=""`
  d'office ; les libellés de section n'étaient pas des titres ; `index.php`
  produisait plusieurs `h1` sur une liste ; le panneau mobile laissait onze
  contrôles tabulables derrière lui ; le bouton d'action était à 3,84:1 pour un
  seuil de 4,5.
- **Gabarits de titre de Yoast** restés en anglais : son paquet de langue
  n'était pas installé au moment de son activation — voir
  [`readme/seo.md`](readme/seo.md).
- **Invalidation du cache des assets** : CSS et JS étaient servis avec la
  version de WordPress sous un `Expires` d'un mois.
- **Amorçage de la page d'accueil joué avant l'activation d'ACF** : les blocs
  partaient sans aucune donnée, et l'idempotence interdisait la correction.
- **Débordement horizontal** et coupe de la carte du hero sur les vues basses.

## 1.0.0 — périmètre initial, non livré

Liste de cadrage conservée telle quelle : ce sont des tickets de périmètre, pas
des livraisons.

- [LCDS-1] - Header
- [LCDS-2] - Footer
- [LCDS-3] - Homepage
- [LCDS-4] - Le cabinet
- [LCDS-5] - L'équipe
- [LCDS-6] - Cas cliniques
- [LCDS-9] - Contact pages
- [LCDS-10] - Foire aux questions
- [LCDS-10] - Legal mentions
- [LCDS-12] - Mobile version
- [LCDS-13] - Optimisation and performance
- [LCDS-14] - Website animations
- [LCDS-17] - 404 page
