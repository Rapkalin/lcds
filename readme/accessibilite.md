# Accessibilité / RGAA

Le socle a été audité sur cinq gabarits — accueil, page, article, 404,
recherche — avec un analyseur du balisage servi et une sonde navigateur qui
calcule les contrastes sur les styles **réels**. Ce qui suit consigne les
décisions, pas la méthode : celle-ci vit dans [`qa.md`](qa.md).

## Audit RGAA 4.1 — relevé du 05/09/2026

Mené sur la **grille officielle** téléchargée depuis
`accessibilite.numerique.gouv.fr` : 106 critères, 13 thématiques, libellés
littéraux. Les numéros cités ailleurs dans cette page viennent de là et non de
mémoire.

Périmètre : les 5 gabarits du thème — accueil, page, article, 404, recherche.

### Non applicable par structure — 36 critères

Décidé sur un inventaire du balisage servi, pas sur une impression :

| Thématique | Critères | Preuve |
| --- | --- | --- |
| 2. Cadres | 2 | 0 `<iframe>` sur les 5 gabarits |
| 4. Multimédia | 13 | 0 `<video>`, `<audio>`, `<object>`, `<embed>` |
| 5. Tableaux | 8 | 0 `<table>` |
| 11. Formulaires | 13 | 0 `<form>` et 0 champ — le formulaire de contact n’est pas en page |

Le jour où le formulaire de contact arrive, **13 critères se rouvrent** d’un
coup : c’est la thématique la plus lourde du référentiel après Multimédia.

### Vérifié et conforme

| Critère | Ce qui a été mesuré |
| --- | --- |
| **1.1** | 26 `<img>` sur les 5 gabarits, toutes avec un `alt` ; le texte vient de la médiathèque |
| **1.2** | les 29 `<svg>` décoratifs portent `aria-hidden="true"` et `focusable="false"` |
| **3.2** | 53 éléments de texte mesurés sur les styles réels, **0 sous le seuil** ; survol des boutons contournés à 11,37:1 |
| **3.3** | glyphes des contrôles à 10,64:1, curseur du carrousel à 5,96:1, remplissage du parcours à 8,84:1 |
| **6.1** | aucun intitulé générique ; les boutons à glyphe portent un texte masqué visuellement |
| **6.2** | 107 liens, **0 sans intitulé** |
| **7.3** | accordéon, cartes, carrousel et panneau mobile actionnables au clavier ; rail focalisable et défilable |
| **8.1** | doctype HTML présent sur les 5 gabarits |
| **8.3** | `<html lang="fr">` sur les 5 |
| **8.4** | contenu français, code `fr` — cohérent |
| **8.5** | titre présent et non vide sur les 5 |
| **8.6** | titres pertinents et **en français** depuis la correction des gabarits Yoast |
| **8.9** | 0 `<b>`, `<i>`, `<u>`, `<font>`, `<center>`, `<big>`, `<strike>`, `<tt>` |
| **9.1** | un seul `h1` par page, **aucun saut de niveau** ; les libellés de section sont des `h2` |
| **9.3** | les listes n’ont que des `<li>` en enfants directs, aucun `<li>` hors liste |
| **10.1** | toute la présentation vient de `dist/main.css` ; 0 balise de présentation |
| **10.2** | **0 contenu textuel généré par `::before`/`::after`** |
| **10.6** | le seul lien en pleine page est distinguable de son texte |
| **10.7** | `:focus-visible` apparié sur **tous** les contrôles affichés ; aucun `outline: none` dans la feuille |
| **10.11** | aucun défilement horizontal à 1440, 500 et **320px réels** |
| **10.12** | espacement surchargé (1.5 / 0,12em / 0,16em / 2em) : **0 troncature** |
| **12.6** | repères `banner`, `navigation`, `main` et `contentinfo` sur les 5 gabarits |
| **12.7** | lien d’évitement présent, visible à la prise de focus, cible existante |
| **12.8** | aucun `tabindex` positif ; panneau mobile ouvert : **0 contrôle tabulable derrière** |
| **12.9** | `inert` retire la page du parcours, Échap referme et rend le focus au bouton |
| **12.10** | 0 `accesskey` |
| **13.1** | aucune limite de temps, 0 `http-equiv="refresh"` |
| **13.8** | aucun contenu en mouvement automatique ; parcours et pied de page sont pilotés par le défilement et désactivés sous `prefers-reduced-motion` |

### Non conforme

| Critère | Constat |
| --- | --- |
| **12.1** | *« Chaque ensemble de pages dispose-t-il de deux systèmes de navigation différents, au moins ? »* — le site n’en a **qu’un**, le menu. Ni moteur de recherche exposé, ni plan du site. WordPress fournit la recherche, le thème ne l’affiche nulle part. |

### Corrigé pendant cet audit

| Critère | Ce qui n’allait pas |
| --- | --- |
| **10.4** | *« Le texte reste-t-il lisible lorsque la taille des caractères est augmentée jusqu’à 200 % ? »* — **non** : toutes les cotes du thème étant en `rem`, la mise en page doublait et la page passait à **1519px pour une vue de 1440**, imposant un défilement horizontal. Corrigé : la page tient désormais à 1440. Une assertion de `bin/qa-front.sh` le vérifie. |

> **Attribution non isolée.** J’ai posé sept règles en même temps (retour à la
> ligne de l’en-tête et des pastilles, plafonds de largeur, `min-width: 0` sur
> les éléments flex, repli de la grille du pied de page). Le lot corrige — 1519
> puis 1440, mesuré — mais ma tentative de bisection a été menée avec un script
> **sans assertions**, donc sans valeur. Je ne sais pas laquelle porte le
> correctif, ni si certaines sont inutiles. À reprendre proprement.

### À vérifier, et pourquoi ça n’a pas pu l’être ici

| Critère | Ce qui manque |
| --- | --- |
| **1.3, 6.1** | La *pertinence* d’une alternative ou d’un intitulé est un jugement humain. Les alternatives actuelles sont de démonstration. |
| **7.1, 7.5** | La restitution par une technologie d’assistance ne se déduit pas du balisage. **Aucun test lecteur d’écran n’a été fait** — le RGAA l’exige. |
| **8.2** | Validité du code : aucun validateur W3C n’a été passé. L’analyse structurelle maison ne relève rien, ce n’est pas la même chose. |
| **8.7** | Changements de langue : « newsletter », « Doctolib », « Cone Beam 3D ». Le référentiel exclut les mots passés dans l’usage — arbitrage à faire. |
| **10.5** | 20 règles déclarent une couleur de police sans fond, **toutes issues des styles de bloc de WordPress** (`.has-black-color`…), aucune du thème. À confirmer. |
| **13.9** | Orientation portrait/paysage : non testé, faute d’appareil réel. |

### Ce qui reste ouvert au-delà du référentiel

- **Le formulaire de contact** rouvrira les 13 critères de la thématique 11.
- **Les polices définitives** (Sligoil, Inter) ne sont pas auto-hébergées : les
  mesures de taille portent sur des polices de repli.
- **320px ET 200 % simultanément** : la page passe à 384px pour 320. Aucun des
  deux critères ne demande ce cumul — 10.4 porte sur la taille du texte, 10.11
  sur la largeur de vue — mais l’écart est consigné.


## Règles à ne pas défaire

**Le libellé de section EST le titre de la section.** `components/tag.php`
accepte un argument `element`, et les trois blocs à étiquette lui passent `h2`.
Sans ça, les libellés « l'histoire », « le parcours de soin » et « Les
différents traitements » ne sont que des `<p>` : la page présentait alors un
`h1` suivi de **onze `h2` frères sans regroupement**, et la section Histoire
n'avait aucun titre.

> Le niveau dit la hiérarchie, la classe dit l'apparence. Les entrées
> d'accordéon et les étapes du parcours sont passées en `h3` **et** leurs
> classes portent maintenant `font-size: $fs-h2`. Vérifié : 48px / interligne
> 58px / boîte 553×116 avant comme après, au pixel.

**Le texte alternatif vit dans la médiathèque.** Voir
[`images.md`](images.md) : ne jamais repasser `'alt' => ''` depuis un composant.

**`index.php` n'émet un `h1` que sur du contenu isolé.** Sur une liste, chaque
résultat en portait un — mesuré : deux `h1` sur `?s=`. Le `h1` d'une liste est
celui de la liste.

**Le panneau mobile rend la page `inert`.** On remonte du panneau jusqu'à
`<body>` en neutralisant les **frères** de la branche. Filtrer sur les enfants
de `<body>` ne suffit pas : le logo est dans le même `<header>` que le panneau,
il restait tabulable. Mesuré à 320px : **onze contrôles** derrière l'overlay
étaient encore dans le parcours de tabulation.

**Les cartes de technologie sont des panneaux à révéler, pas des sections de
texte masquées en CSS.** Même contrat que l'accordéon — `aria-expanded`,
`aria-controls`, panneau réellement `hidden` — et le **même code**, sélectionné
par `data-disclosure` plutôt que par une classe de composant. Deux copies de
cette boucle auraient divergé.

## Un voile ajouté hors maquette

Le titre d'une carte de technologie est **blanc sur une photo fournie par un
contributeur** : son contraste n'est pas mesurable, et une photo claire le
rendrait illisible. `.tech-card__scrim` borne le pire cas par un dégradé sombre
en haut de la carte.

Ce n'est pas dans la maquette — le designer a choisi des photos sombres en haut.
**À lui valider.** Sans ce voile, rien ne garantit la lisibilité dès que le
client changera de visuel.

## Contrastes : ce qui est mesuré

| Couple | Ratio | Verdict |
| --- | --- | --- |
| Bleu `#00387A` sur blanc | 11,37:1 | conforme partout |
| Blanc sur bleu | 11,37:1 | conforme partout |
| Turquoise `#048B8C` sur blanc | 4,14:1 | **échec** pour du texte < 24px |
| Orange `#E25304` sur blanc | 3,84:1 | **échec** pour du texte < 24px |
| Blanc sur `$orange-on-text` `#C43F04` | 5,17:1 | conforme |
| `#A8BED6` sur blanc | 1,91:1 | bordures et pistes seulement |
| `#D9E4F1` sur blanc | 1,29:1 | filets seulement |

`$orange-on-text` existe **uniquement** pour porter du texte blanc : le bouton
« Prendre RDV » de l'en-tête était à 3,84:1 en 13px, sous le seuil de 4,5.
`$orange` reste la couleur des objets graphiques, où le seuil est de 3.

> **À faire arbitrer par le design.** Deux oranges pour une seule intention est
> un compromis, pas une cible : idéalement la bibliothèque Figma porte les deux
> teintes. Même remarque pour le turquoise, qui ne doit jamais recevoir du texte
> blanc de moins de 24px.

## Ce qui n'est PAS un défaut, et pourquoi

Ces mesures échouent au seuil de 3:1 sans enfreindre pour autant le critère de
contraste des composants non textuels, qui ne porte que sur ce qui est
**nécessaire pour identifier** un composant ou son état :

| Mesure | Ratio | Pourquoi c'est acceptable |
| --- | --- | --- |
| Bordure des boutons du carrousel | 1,79:1 | la flèche à l'intérieur est à 10,64:1 |
| Piste du carrousel | 1,79:1 | l'état est porté par le curseur, à 5,96:1 sur la piste |
| Piste de progression du parcours | 1,20:1 | le remplissage est à 8,84:1 sur la piste |
| Bordure de l'étiquette de section | 1,79:1 | purement décorative, le texte porte l'information |

Les relever déviateraient des cotes relevées au pixel sans gain
d'accessibilité. Ce sont, à noter, les deux couleurs **absentes des variables de
la bibliothèque Figma** — à faire promouvoir côté design.

**La carte blanche du hero** repose sur la photo, pas sur un aplat : le
contraste de sa frontière n'est **pas mesurable** avant les visuels définitifs.
À revérifier à ce moment-là.

## Ce qui reste à vérifier

- **La restitution par un lecteur d'écran** — le RGAA l'exige, elle n'a pas été
  faite. La campagne prouve le balisage et les mesures, pas ce qu'annonce la
  synthèse vocale.
- **Une passe clavier à la main** : `:focus-visible` est apparié par focus
  programmatique.
- **Les polices définitives** : Sligoil et Inter ne sont pas encore
  auto-hébergées, les tailles mesurées portent sur des polices de repli.
- **Le formulaire de contact** : pas encore en page, la thématique Formulaires
  est intacte.
- **Le pied de page** : pas encore développé, donc pas de plan du site ni
  d'accès multiples à évaluer.
