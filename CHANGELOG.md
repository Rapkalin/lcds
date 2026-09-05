# Journal des modifications

Une section par version. La version fait foi dans **`composer.json`** — c'est
elle que le pied de page du site affiche, via `lcds_site_version()`.

> **À tenir à jour à chaque livraison.** Voir la règle 8 de
> [`CLAUDE.md`](CLAUDE.md) : une version qui bouge sans entrée ici rend le
> journal inutile, et une entrée sans version rend la version fausse.

## 2.3.0

### Corrigé

- **RGAA 10.4 — le texte à 200 % rendait la page illisible sans défilement
  horizontal.** Toutes les cotes du thème étant en `rem`, la mise en page
  doublait avec le texte : la page passait à 1519px pour une vue de 1440. Elle
  tient désormais à 1440, et une assertion de la campagne le vérifie.
- **Les boutons contournés s'inversent au survol** : fond bleu, texte blanc.
  Ils restaient bleus sur blanc.

### Ajouté

- **Audit RGAA 4.1 complet** dans `readme/accessibilite.md`, mené sur la grille
  officielle : 36 critères non applicables sur preuve structurelle, 28 vérifiés
  conformes, 1 non conforme (12.1 — un seul système de navigation), 6 hors de
  portée d'une vérification automatique.

## 2.2.1

### Corrigé

- **Le visuel révélé était 32px trop haut.** Sa boîte fait un rayon de plus que
  ce qu'on découvre — le débord passe sous le panneau — et il était donc centré
  sur la boîte plutôt que sur la partie réellement vue. Le décalage est borné :
  sur une vue étroite la photo n'a plus de débord vertical, et le compenser
  quand même redécouvrait l'encoche.

## 2.2.0

### Ajouté

- **Le cadrage du visuel révélé est contribuable** : haut, centre ou bas. Le
  visuel étant rogné pour remplir la largeur, la bande qu'il montre relève du
  contenu et non du gabarit.

### Changé

- **L'écran de configuration passe sous *Réglages → Configuration***, au lieu
  d'une entrée de premier niveau dans le menu d'administration.

## 2.1.1

### Corrigé

- **Liseré blanc dans les coins du pied de page.** Le visuel démarrait là où le
  panneau s'arrête ; les encoches de ses coins arrondis laissaient donc voir le
  fond du bloc. Le visuel remonte désormais d'un rayon sous le panneau, à tout
  avancement de l'animation.

## 2.1.0

### Ajouté

- **Pied de page**, relevé au pixel sur le PDF de maquette — trois blocs
  d'appel, adresse, deux menus, logo et mention de copyright.
- **Sa révélation** : le panneau masque un visuel pleine largeur et se soulève
  en fin de page pour le découvrir. Désactivée sous `prefers-reduced-motion`,
  où le visuel reste simplement visible.
- **Écran « Réglages du site »** (page d'options ACF) : le pied de page est
  commun à toutes les pages, son contenu n'appartient à aucune d'elles. Sa
  structure est versionnée en JSON local comme le reste.
- **Les menus du pied de page sont amorcés** — navigation et liens légaux.

## 2.0.1

### Corrigé

- **La configuration des champs ne peut plus diverger du dépôt.** L'interface de
  gestion des groupes ACF est masquée hors développement, et les clés propres à
  la machine — dont `local_file`, un chemin absolu — sont retirées du JSON après
  chaque écriture. Sans ça le fichier différait d'une machine à l'autre sans
  qu'aucun champ n'ait bougé.

## 2.0.0

**Rupture de contribution.** La page d'accueil ne se contribue plus en blocs de
l'éditeur mais par un **champ de contenu flexible**, sur le modèle de 2bdm. Un
contenu saisi avant cette version doit être ressaisi : il vivait dans
`post_content`, il vit désormais en post meta.

### Changé

- **Les six sections de la page d'accueil sont les layouts d'un unique champ**
  `sections`, dans un seul formulaire sous l'éditeur. Elles s'ajoutent, se
  réordonnent et se suppriment au glisser-déposer.
- **Le catalogue est scellé à la page d'accueil** (`page_type == front_page`).
  Aucune de ces sections ne peut atterrir sur une autre page — c'était le
  principal défaut du modèle en blocs.
- **L'éditeur de blocs est coupé page par page**, pas globalement : seuls les
  contenus listés par `lcds_acf_contributed_posts()` le perdent. Le texte libre
  continue de s'écrire en blocs.
- Le titre `h1` est devenu un champ de la **page** et non de sa première
  section.
- Les champs restent **versionnés en JSON local**, contrairement à 2bdm où ils
  ne vivent qu'en base.

### Retiré

- Les six blocs `acf/lcds-*`, `inc/blocks.php`, la catégorie d'insérateur, et
  l'aperçu dans le canevas — avec les `add_editor_style` et les assertions de QA
  qui allaient avec.

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
