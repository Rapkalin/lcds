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
| `226:1211` header | 0 | 128 | Voir [son relevé](226-1211-header.md) |
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
