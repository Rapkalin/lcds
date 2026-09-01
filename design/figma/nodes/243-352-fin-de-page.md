# `243:352` — Frame 54 (blocs de fin + footer)

Relevé **partiel** du 31/08/2026 : `get_metadata` seul. Cadre 1440 × 4724.

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
`user-circle-single--…`) : à extraire en SVG au moment de l'implémentation.
