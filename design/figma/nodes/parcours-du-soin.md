# Parcours du soin — carrousel de 6 étapes

Relevé **complet** depuis les PDF (01/09 puis 04/09/2026). **Implémenté** le
04/09/2026 — `components/block-journey.php`.

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

**La barre de progression : question tranchée.** Mesurée au pixel sur les six
étapes, c'est un remplissage **continu** valant `étape / 6` :

| Étape | Rempli | Mesuré | n/6 |
| --- | --- | --- | --- |
| 01 | 111px | 16,6 % | 16,67 % |
| 02 | 222px | 33,3 % | 33,33 % |
| 03 | 333px | 50,0 % | 50 % |
| **04** | **400px** | **60,1 %** | **66,67 %** |
| 05 | 555px | 83,3 % | 83,33 % |
| 06 | 666px | 100 % | 100 % |

Cinq sur six tombent exactement sur `n/6`. **L'étape 04 est une erreur de
maquette** : elle a été dessinée sur une grille de cinq segments (3 × 133,2 ≈
400). La règle `n/6` est implémentée, et l'avancement est continu — plus fluide
qu'un saut d'étape en étape.

**Sligoil est une police à chasse fixe.** Établi sur cette capture — titre et
numéro d'étape monospacés, zéro barré — puis confirmé chez Velvetyne. Le repli
CSS doit être monospace.

## Champs à prévoir

Durée **optionnelle**, visuels en **répéteur** de 0 à 2 avec un ordre variable :
ni deux emplacements figés, ni un champ obligatoire.

## Structure et rythme

Chaque diapositive fait 1440 × 900. Étiquette à `x=161, y=128` ; bloc de contenu
à `x=613` sur 666, décomposé en `101 + 12 + 553` — colonne du numéro, gouttière,
colonne de texte.

| Élément | Position |
| --- | --- |
| Barre de progression | `y=128`, 1px, 666 de large |
| Contenu | `y=177` (barre + 48) |
| Titre → paragraphe | 24px |
| Entre blocs (texte, badge, visuels) | 48px |
| Badge de durée | 86 × 30, bordure `#00387A`, fond transparent |
| Visuels | 165 de haut, `214 + 12 + 327` |

**L'étiquette est hors du flux.** Sur la maquette elle partage la ligne de la
barre, mais sa hauteur de 29px ne doit pas repousser le rail : dans le flux, tout
descendait de 28px. Elle est de toute façon immobile d'une étape à l'autre.

## Défilement

La section est haute de six écrans ; à l'intérieur, une vue collée contient un
rail décalé selon l'avancement. **Une seule variable CSS pilote le rail ET la
barre** : ils ne peuvent pas se désynchroniser.

**L'épinglage est activé par le JavaScript, jamais par défaut.** Sans script, ou
si l'utilisateur demande à réduire les animations, les six étapes s'empilent
verticalement et tout reste lisible. Le défilement détourné est précisément ce
qu'une personne sensible au mouvement doit pouvoir éviter.

> La barre reste affichée en mode empilé, remplie à 1/6. Elle y indique le
> nombre d'étapes plutôt qu'une position — choix assumé, à revoir si ça gêne.
