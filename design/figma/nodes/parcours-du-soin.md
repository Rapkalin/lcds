# Parcours du soin — carrousel de 6 étapes

Relevé **partiel** du 31/08/2026 : `get_metadata` sur les six nœuds, plus une
capture de l'étape 02 qui a corrigé plusieurs lectures.

**Navigation au scroll** — décision du client (01/09/2026). Aucun bouton
précédent/suivant n'existe dans la maquette, confirmé visuellement sur l'étape 02
et non seulement par métadonnées.

| Nœud | Étape | Titre | Durée | Visuels |
| --- | --- | --- | --- | --- |
| `87:303` | 01 | Première consultation | — | 2 (327 + 214) |
| `71:155` | 02 | Bilan orthodontique | 30 min | 2 (214 + 327) |
| `71:190` | 03 | Compte-rendu | 45 min | 1 (327) |
| `71:211` | 04 | Pose de l'appareillage | — | 2 (214 + 327) |
| `189:347` | 05 | Rendez-vous de suivi de contrôle et d'activations | — | 2 (214 + 327) |
| `189:361` | 06 | Dépose de l'appareil et contention | — | 0 |

## Gabarit d'une diapositive

Cadre de 1440 × 900, **fond `#F2F8FF`** — un aplat de section, pas un simple
accent. Le tag à `x=161`, le bloc de contenu à `x=613` sur 666 de large.

Dans le bloc : la barre de progression sur 1 px de haut, puis à 49 px en dessous
la colonne du numéro (101, style CTA) et la colonne de texte (553) séparées de
12 px. Titre en `H2/desktop`, texte en `Paragraph/desktop`, badge de durée
optionnel (86 × 30, contour bleu, coins pleinement arrondis), puis les visuels
sur 165 px de haut, coins de 8 px.

Les emplacements de visuels sont des aplats gris dans la maquette.

## Deux points à trancher

**La barre de progression est incohérente d'une étape à l'autre** : 6 segments de
111 px (01, 02, 05), 2 de 333 (03), 5 de 133,2 (04), 1 de 666 (06) — toujours
pour 666 au total. La capture de l'étape 02 montre une portion sombre d'environ
un tiers, ce qui correspond à 2 segments sur 6. **Interprétation retenue :
6 segments égaux, les N premiers remplis.** Non vérifié sur les six étapes.

**Sligoil est une police à chasse fixe.** Établi sur cette capture — titre et
numéro d'étape monospacés, zéro barré — puis confirmé chez Velvetyne. Le repli
CSS doit être monospace.

## Champs à prévoir

Durée **optionnelle**, visuels en **répéteur** de 0 à 2 avec un ordre variable :
ni deux emplacements figés, ni un champ obligatoire.
