# `12:207` — LCDS_hp full (page d'accueil)

Relevé **partiel** du 31/08/2026 : `get_metadata` seul. Positions et tailles sont
fiables ; **l'absence d'un élément ne l'est pas** — c'est précisément sur ce nœud
que l'outil a masqué le contenu du hero.

Cadre : 1440 × 4268.

## Grille relevée

| | Valeur |
| --- | --- |
| Gouttière interne | 12 px, constante partout |
| Contenu | 1118 px, marges de 161 px |
| Gouttières du header | 48 px |
| Retrait supérieur de section | 128 px |
| Module récurrent | 52 px (boutons, pastilles, piste) |

Deux découpages, tous deux à 1118 avec 12 px de gouttière :

- **553 + 12 + 553** — libellé à gauche, contenu à droite (intro, bloc contact) ;
- **440 + 12 + 666** — libellé étroit, bloc large (accordéon, parcours).

Et `666 = 101 + 12 + 553` : colonne du numéro, gouttière, colonne de texte.

## Sections

| Enfant | y | Hauteur | Contenu |
| --- | --- | --- | --- |
| `226:1189` hero | 0 | 900 | Voir [son relevé](226-1189-hero.md) |
| `226:1211` header | 0 | 128 | **Recouvre le hero**, voir [son relevé](226-1211-header.md) |
| `226:1191` Desktop - 1 | 900 | 1328 | Intro + galerie horizontale |
| `17:408` Desktop - 4 | 2228 | 1249 | Accordéon des traitements |
| `87:303` Desktop - 7 | 3368 | 900 | Parcours du soin, étape 01 |

`Desktop - 4` et `Desktop - 7` se chevauchent de 109 px sur le canevas. Sans
conséquence.

## Galerie horizontale de l'intro (`226:1191`)

- `Frame 25` : le tag à gauche, le texte de présentation et un CTA à droite
  (x=565, 553 de large).
- `Frame 37` : le rail. Posé à `x=161` sur **1279 de large**, donc bord droit
  collé aux 1440 — **plein-bord droit assumé**. Ses enfants vont jusqu'à
  `x = 2698`, soit plus du double de la largeur visible.
  Largeurs : 892, 553 (scindé en deux de 309,5), 666, 503, puis un reliquat de
  36 px qui sert d'amorce visuelle.
- `Frame 39` : les contrôles, 1118 de large.
  - piste de progression à `y=25`, haute de 2 px : un segment plein de 162 px
    puis un segment de 52 px à `x=214` ;
  - deux boutons de 52 × 52 à `x=1002`, chevauchés de 12 px.

> **Ce bloc de contrôles est identique à celui du carrousel Technologies** des
> blocs de fin — mêmes dimensions, même position. Un seul composant à écrire.

## Accordéon des traitements (`17:408`)

Cinq entrées dans un bloc de 666, séparées par des filets de 1 px, chacune avec
un bouton de 52 × 52 à droite :

`Interceptif` · `Gouttières / Aligneurs` · `Multibagues` · `Orthèses pour l'apnée
du sommeil` · `Protège-dents`

Seule **Multibagues** porte un paragraphe : c'est l'**état ouvert** qui a été
maquetté. Les autres états sont à demander au designer. Un CTA ferme le bloc.

## L'en-tête recouvre le hero

Le hero et l'en-tête sont tous deux à `y = 0` : **l'en-tête est transparent et
posé par-dessus la photo.** C'est pour cela que chaque lien de navigation porte
une pastille blanche et le bouton d'action une pastille orange — sans elles, le
texte serait illisible sur le visuel.

Vérifié au pixel sur le PDF : `#BEDAFF` (le ciel) au-dessus de la navigation,
`#FFFFFF` seulement derrière les liens.

> Conséquence pratique : construire un en-tête sans le hero sous lui masque
> l'omission. C'est ainsi qu'un fond blanc et l'absence de pastilles sont passés
> inaperçus à l'étape 1.

## Section intro + galerie — implémentée

`components/block-intro.php`, `components/tag.php`, `components/cta.php` et
`components/carousel.php`, mesurés contre les cotes du PDF :

| Mesure | Attendu | Obtenu |
| --- | --- | --- |
| Section, haut | 900 | 900 |
| Étiquette, x / haut | 161 / 1028 | 161 / 1028 |
| Bloc de texte, hauteur | 154 | 154 |
| Rail, haut / hauteur | 1339 / 629 | 1339 / 629 |
| Rail, bord droit | 1440 | 1440 |
| Éléments, largeurs | 892 / 553 / 666 / 503,2 / 36 | identiques |
| Visuel empilé, hauteur | 309,5 | 309,5 |
| Piste, x / largeur / haut | 161 / 214 / 2073 | identiques |
| Boutons, bord droit | 1279 | 1279 |

### Deux points restés en suspens

**La piste de progression.** La maquette la dessine remplie sur 162 des 214px,
et ce **sur les deux carrousels** — même valeur, ce qui trahit un état dessiné
une fois plutôt qu'un état calculé. Aucune lecture ne colle au contenu réel :
la part visible vaut 1279/2698 = 47 %, pas 76 %. Implémenté en indicateur de
défilement : largeur du curseur = part visible, position = avancement. À
confirmer avec le designer.

**L'état désactivé des boutons** en début et fin de course n'existe pas dans la
maquette. Proposition : opacité réduite.

**La couleur `#A8BED6`** (bordures, piste inactive) est dans la maquette mais
absente des variables de bibliothèque. À faire promouvoir côté design.
