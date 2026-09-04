# Accessibilité / RGAA

Le socle a été audité sur cinq gabarits — accueil, page, article, 404,
recherche — avec un analyseur du balisage servi et une sonde navigateur qui
calcule les contrastes sur les styles **réels**. Ce qui suit consigne les
décisions, pas la méthode : celle-ci vit dans [`qa.md`](qa.md).

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
