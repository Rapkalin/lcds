# `243:352` — Frame 54 (blocs de fin + footer)

Relevé `get_metadata` du 31/08/2026, **complété au pixel par le PDF**
`HP_06_Frame 54.pdf` le 04/09/2026 — sans appel Figma supplémentaire. Cadre
1440 × 4724, confirmé par `pdfinfo`.

> **Le relevé Figma était trompeur sur un point.** Il annonçait des cartes
> Technologies « légèrement décalées verticalement (0, 11,4 ou 23,4) — un effet
> d'ondulation ». Mesuré sur le rendu du PDF, les trois cartes partagent en
> réalité **le même centre vertical** : ce qui change est leur **rotation**
> (+2,88 / 0 / −2,88 degrés). Les 0 / 11,4 / 23,4 étaient les hauteurs de boîte
> englobante de cadres pivotés. Une deuxième illustration de la règle : ce que
> `get_metadata` renvoie décrit la boîte, pas l'intention.

| Enfant | y | Hauteur | Contenu |
| --- | --- | --- | --- |
| `189:361` Desktop - 10 | 0 | 900 | Parcours du soin, étape 06 |
| `17:386` Desktop - 2 | 900 | 926 | Carrousel Technologies |
| `307:623` Desktop - 11 | 1826 | 1025 | Infos pratiques du cabinet |
| `124:1016` Desktop - 8 | 2851 | 618 | **À ignorer** — diapositive supplémentaire, relève de la contribution (décision client, 01/09/2026) |
| `276:1063` footer | 3469 | 1255 | Pied de page, non relevé |

## Carrousel Technologies (`17:386`)

- En-tête de section : un tag à gauche, deux `item` à droite (45 et 279 de large)
  dans un bloc de 320 — vraisemblablement un filtre ou une pagination textuelle.
- Rail : posé à `x=161` sur **1279 de large**, plein-bord droit comme la galerie
  d'intro. **Neuf cartes** allant jusqu'à `x = 4291`, en largeurs alternées
  471,5 / 447,5 et légèrement décalées verticalement (0, 11,4 ou 23,4) — un
  effet d'ondulation, pas un alignement. Terminé par le même reliquat de 36 px.
- Contrôles : **strictement identiques à ceux de la galerie d'intro** — piste
  `162 + 52` à `y=25`, deux boutons de 52 × 52 à `x=1002` dans un bloc de 1118.

> Deux carrousels partagent donc exactement le même bloc de contrôles. À écrire
> une fois, instancier deux fois.

### Cotes mesurées sur le PDF — implémenté

| Mesure | Attendu | Obtenu |
| --- | --- | --- |
| Étiquette, bord gauche / hauteur | 161 / 30 | identiques |
| Bouton d'action, bord droit | 1279 | 1279 |
| Rail, bord gauche / bord droit | 161 / 1440 | identiques |
| Rail, hauteur | 494 | 494 |
| Cartes, largeurs | 471,5 / 447,5 / 471,5 | identiques |
| Cartes, inclinaisons | +2,88 / 0 / −2,88 | identiques |
| Cartes, hauteur | 470 | 470 |
| Piste, bord gauche / largeur | 161 / 214 | identiques |
| Boutons, bord droit | 1279 | 1279 |

Le rail est de 494 et non de 470 : la boîte englobante d'une carte de 471,5 ×
470 pivotée de 2,88° mesure 493,1 de haut. Un rail à 470 rognait les coins ou
faisait apparaître une barre de défilement verticale — le rail impose
`overflow-x`, donc `overflow-y: auto`.

Chaque carte porte un titre et un bouton qui **révèle son texte par-dessus la
photo**. La maquette dessine la première carte OUVERTE, titre masqué : ça se lit
comme une démonstration de l'état ouvert, comme pour l'accordéon. Même contrat
que lui — `aria-expanded`, `aria-controls`, panneau réellement `hidden` — et le
même code, sélectionné par `data-disclosure`.

## Infos pratiques (`307:623`)

Découpage 553 + 12 + 553. À gauche un tag et un aplat de 440 × 549 (une carte,
probablement). À droite cinq entrées, chacune composée d'une icône de 24 px, d'un
titre en `H3/desktop` et d'un contenu, séparées par des filets de 1 px :

| Entrée | Contenu relevé |
| --- | --- |
| Adresse du cabinet | `cabinet d'orthodontie lcds`, `2 place Saint-Maurice 38200 Vienne`, avec un CTA |
| Moyens de transport | Bus — Jardin de Ville (lignes 1, 6, 5, 4 et 7), Saint-Maurice (mêmes lignes), SNCF Brillier (ligne 7) |
| Accessibilité | Entrée accessible, parking gratuit |
| Horaires | Du lundi au vendredi 09:00-19:00, samedi 08:00-13:00 |
| Contact | `+33 (0) 4 74 78 33 22`, `contact@lacliniquedusourire.com` |

Les icônes portent des noms d'un jeu tiers (`location-target-2--…`,
`school-bus-side`, `information-circle--…`, `search-history-browser`,
`user-circle-single--…`) dont le projet n'a pas les fichiers. Elles ont été
**redessinées** en 24 × 24, trait de 1,5, en `currentColor` — à remplacer par
les assets du designer.

### Cotes mesurées sur le PDF — implémenté

| Mesure | Attendu | Obtenu |
| --- | --- | --- |
| Découpage des colonnes | 553 + 12 + 553 | identique |
| Visuel de gauche | 440 × 549 | 440 × 549 |
| Colonne de droite, bords | 726 → 1279 | identiques |
| Icône, largeur | 24 | 24 |
| Texte, bord gauche | 774 | 774 |
| Bouton contourné, bord droit / hauteur | 1279 / 30 | identiques |
| Filets | 1px `#D9E4F1`, 48 de part et d'autre | identiques |

Le bouton « voir le plan » n'est pas le bouton d'action des autres sections :
une seule pastille contournée, sans glyphe, 131 × 30 contre 321 × 30. D'où la
variante `outline` du composant `cta`.

Les filets tombent à y=274, 475, 625 et 771 de la bande, soit 48px de part et
d'autre — le même rythme que l'accordéon des traitements.
