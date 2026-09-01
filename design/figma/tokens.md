# Variables de bibliothèque

Relevé du 31/08/2026 — `get_variable_defs`. **Ne pas redemander** : ces variables
sont celles de la bibliothèque, pas d'un nœud. Deux nœuds racines très éloignés
(`12:207` et `243:352`) ont renvoyé les mêmes huit entrées.

Fichier Figma : `kfPEAXr7clNODJUq5rT9GF` — LCDS | UI

## Couleurs

| Nom Figma | Valeur |
| --- | --- |
| `BLEU` | `#00387A` |
| `TURQUOISE` | `#048B8C` |
| `ORANGE` | `#E25304` |
| `#F2F8FF` | `#F2F8FF` |

## Styles de texte

| Nom Figma | Police | Taille / interligne | Interlettrage |
| --- | --- | --- | --- |
| `H2/desktop` | Sligoil Micro | 48 / 1.2 | 0 |
| `H3/desktop` | Sligoil Micro | 24 / 1.2 | 0 |
| `Paragraph/desktop` | Inter SemiBold (600) | 16 / 1.4 | 0 |
| `CTA` | Inter Medium (500) | 13 / 1 | 8 % |

Les suffixes `/desktop` laissent entendre qu'il existe des variantes mobiles dans
la bibliothèque. Elles n'apparaissent pas dans les relevés faits : **aucun cadre
mobile n'a été maquetté à ce jour.**

Transposition en SCSS : `website/app/themes/lcds/assets/styles/basics/variables.scss`.
Les décisions de transposition (interlettrage en `em`, interlignes sans unité,
repli monospace pour Sligoil) sont expliquées dans
[`../../readme/front.md`](../../readme/front.md).
