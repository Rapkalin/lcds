# Carte des nœuds

Fichier Figma : `kfPEAXr7clNODJUq5rT9GF` — LCDS | UI
URL d'un nœud : `https://www.figma.com/design/kfPEAXr7clNODJUq5rT9GF/LCDS-%7C-UI?node-id=<id-avec-tiret>`

## Page d'accueil

| Nœud | Nom | Rôle | Relevé |
| --- | --- | --- | --- |
| `12:207` | LCDS_hp full | Racine de la page d'accueil (1440 × 4268) | [structure](nodes/12-207-accueil.md) — partiel |
| `226:1211` | header | En-tête : logo, 4 liens, CTA | [complet](nodes/226-1211-header.md) |
| `226:1189` | hero | Photo pleine page + carte « Prendre RDV » | [complet](nodes/226-1189-hero.md) |
| `226:1191` | Desktop - 1 | Intro + galerie horizontale | non relevé |
| `17:408` | Desktop - 4 | Accordéon des traitements | [structure](nodes/12-207-accueil.md) — partiel |
| `243:352` | Frame 54 | Blocs de fin + footer (1440 × 4724) | [structure](nodes/243-352-fin-de-page.md) — partiel |
| `276:1063` | footer | Pied de page (1440 × 1255) | non relevé |

## Parcours du soin — 6 étapes, un carrousel au scroll

| Nœud | Étape |
| --- | --- |
| `87:303` | 01 — Première consultation |
| `71:155` | 02 — Bilan orthodontique |
| `71:190` | 03 — Compte-rendu |
| `71:211` | 04 — Pose de l'appareillage |
| `189:347` | 05 — Rendez-vous de suivi |
| `189:361` | 06 — Dépose et contention |

Détail : [nodes/parcours-du-soin.md](nodes/parcours-du-soin.md) — partiel.

## Reste à relever

Par ordre de besoin, **un appel `get_design_context` par ligne** :

1. `226:1191` — intro et galerie horizontale (étape 2 du développement) ;
2. un parent commun des six étapes du parcours, si la maquette en a un — sinon
   `71:155` seul suffira à établir le gabarit, les cinq autres n'étant que du
   contenu ;
3. `243:352` — blocs de fin, y compris le carrousel Technologies ;
4. `276:1063` — footer.

> Les nœuds marqués « partiel » n'ont été relevés qu'avec `get_metadata`, qui
> **n'est pas exhaustif** : positions et tailles sont fiables, l'absence d'un
> élément ne l'est pas. Voir [README.md](README.md).
