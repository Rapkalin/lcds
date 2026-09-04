# Contribution du contenu

Chaque section du site est un **bloc de l'éditeur**. Un contributeur assemble une
page en insérant des blocs et en remplissant leurs champs — aucun contenu ne vit
dans le code.

## Où modifier la page d'accueil

**Pages → Accueil → Modifier.** Les quatre sections y sont déjà en place ; les
sélectionner ouvre leurs champs dans le panneau de droite.

La page est créée par `bin/init.sh` au premier démarrage d'un environnement, puis
**désignée comme page d'accueil du site** (Réglages → Lecture). Tous les champs
sont garnis, y compris les images là où des visuels de démonstration existent :
sélectionner un bloc doit montrer la section, pas un cadre vide.

> L'amorçage est idempotent : une page déjà en place n'est **jamais** réécrite.
> Le contenu saisi ne peut donc pas disparaître au redémarrage d'un conteneur.
> Pour la recréer volontairement :
> `dwp eval-file bin/seed-homepage.php force`.

## Les blocs

Tous sont regroupés dans la catégorie **LCDS** de l'insérateur.

| Bloc | Ce qu'il porte |
| --- | --- |
| **LCDS — Hero** | Visuel pleine largeur, carte d'appel, et le **titre `h1`** de la page |
| **LCDS — Texte et galerie** | Étiquette, texte, bouton, rail de visuels défilable |
| **LCDS — Accordéon** | Étiquette, entrées dépliables, bouton |
| **LCDS — Parcours en étapes** | Étiquette, étapes numérotées avec durée et visuels |

### Le titre h1

Les maquettes ne prévoient **aucun titre visible** dans le hero. Le champ
« Titre principal (h1) » est donc rendu **masqué visuellement** : invisible à
l'écran, mais lu par les moteurs de recherche et les lecteurs d'écran.

Sans lui, la page n'a aucun `h1` — les titres de l'accordéon et des étapes sont
des `h2`. Y placer l'expression clé du référencement, par exemple
« Cabinet d'orthodontie à Vienne ».

### Les formes de cadre

Les largeurs du rail et des visuels d'étape sont un **choix graphique** de la
maquette, pas une propriété des photos. Un contributeur choisit donc une forme
nommée — « Grand », « Deux visuels empilés », « Moyen », « Petit » — jamais un
nombre de pixels.

Les largeurs vivent dans `inc/enums/LcdsMediaShape.php`, **source unique**. Les
listes déroulantes de l'administration sont alimentées depuis cette enum par un
filtre `acf/load_field` : elles ne peuvent pas en divorcer.

### La couleur de la puce

Le contributeur choisit **« Vert »** ou **« Rouge »** — les noms du client. Ce ne
sont pas les noms du système de design : la bibliothèque Figma et la feuille de
style connaissent ces deux couleurs sous `turquoise` (`#048B8C`) et `orange`
(`#E25304`), et **seul le libellé a changé**.

À savoir avant de toucher à `inc/enums/LcdsDotColor.php` : un contributeur qui
dit avoir choisi « Rouge » a produit `dot => 'orange'` et la classe
`.tag--orange`. Renommer la valeur pour l'aligner sur le libellé orphelinerait
`components/tag.scss` **et** viderait la couleur de toutes les étiquettes déjà
enregistrées — sans erreur, une classe absente donnant une puce transparente.
`tests/Unit/DotColorTest.php` fait échouer la CI si quelqu'un s'y essaie.

Comme pour les formes, les choix ne sont pas recopiés dans les trois groupes de
champs : ils viennent de l'enum, par un filtre `acf/load_field/name=puce`.
Accroché sur le **nom** du champ et non sur sa clé, les trois blocs à étiquette
ayant chacun la leur.

### Ce qui suit le nombre d'entrées

- **Rail de visuels** : plus il y a de visuels, plus il défile. Rien ne plafonne
  leur nombre — voir [`../design/figma/nodes/12-207-accueil.md`](../design/figma/nodes/12-207-accueil.md).
- **Parcours** : la numérotation et la barre de progression se déduisent du
  nombre d'étapes. En ajouter une renumérote et rééchelonne tout.

## Où l'on saisit : dans l'éditeur, pas dans la colonne de droite

Les quatre blocs sont en **`"mode": "auto"`** (clé `acf` de leur `block.json`) :

- **bloc non sélectionné** → son aperçu, à la place qu'il occupe dans la page ;
- **bloc sélectionné** → ACF remplace l'aperçu par son **formulaire, dans le
  canevas**, sur toute la largeur de l'éditeur.

C'est le seul emplacement hors inspecteur qu'ACF sache servir. En mode
`preview`, ses champs sont rendus dans `InspectorControls` — la colonne de
droite — et ça n'est pas configurable : c'est câblé dans le JavaScript livré du
plugin. Un vrai panneau *sous* l'éditeur voudrait dire une metabox, donc un
groupe de champs rattaché à la **page** et non au bloc : plus de réordonnancement,
plus de section répétable, l'architecture en composants tombe.

Contrepartie du mode `auto` : le bouton « Passer en édition » de la barre
d'outils disparaît, ACF le masque quand le mode est automatique. Repasser les
quatre `block.json` en `"mode": "preview"` rétablit ce bouton et renvoie les
champs à droite — un `sed` suffit.

## L'aperçu dans l'éditeur

L'aperçu est produit par le **même code** que le rendu public, appelé par ACF
avec `$is_preview = true`. Ce que voit le contributeur est donc ce que verra le
visiteur.

Deux conditions pour que ça tienne :

- **La feuille du thème est servie à l'éditeur** (`add_editor_style` dans
  `inc/blocks.php`). Sans elle les blocs s'affichent, mais nus : personne ne
  reconnaît la section qu'il modifie.
- **Les champs sont remplis.** Un bloc vide n'a rien à dessiner ; c'est
  précisément pourquoi l'amorçage garnit tout.

Deux écarts assumés avec le rendu public, tous deux dus à l'absence de
JavaScript dans l'éditeur : le rail de la section Histoire ne défile pas, et les
étapes du parcours **s'empilent verticalement** au lieu de se dérouler
horizontalement. C'est le rendu de repli du site, celui que voient déjà les
visiteurs sans JavaScript ou ayant demandé à réduire les animations.

> Servir la feuille du thème à l'éditeur la fait aussi porter sur le formulaire
> d'ACF, qui vit désormais dans le canevas : le thème pose des règles sur `p` et
> `a`, les libellés et liens du formulaire les subissent. Cosmétique, et à
> surveiller si une règle d'élément est ajoutée à `basics/general.scss`.

## D'où viennent les visuels de démonstration

L'amorçage ne fabrique aucune image : il lit la correspondance
emplacement → identifiant que `bin/seed-demo.sh` écrit dans l'option
`lcds_demo_media`. Ce script extrait les photos des PDF de maquette ; il est
**hors du dépôt**, car il dépend de maquettes qui n'y sont pas.

Conséquence : sur un environnement où il n'a pas tourné — intégration continue,
préproduction — les textes sont là et les champs image restent vides. Le rendu se
dégrade, l'amorçage ne casse pas.

`bin/seed-demo.sh` **réamorce la page en fin de course**. C'est indispensable :
`bin/init.sh` a déjà créé la page avant que ces visuels existent, et l'amorçage
est idempotent — sans ce passage forcé, les champs image resteraient vides. Ce
réamorçage **écrase le contenu saisi dans l'éditeur** ; le script l'annonce.

## Ce qui n'est pas de la copie client

Les maquettes portent du **lorem ipsum** à deux endroits : le texte de la carte
du hero, et le panneau déplié de l'accordéon. L'amorçage y met une rédaction de
démonstration, à faire remplacer par le client.

Les boutons pointent vers `#` : les pages cibles n'existent pas encore. Un lien
**vide** ferait disparaître le bouton — le composant CTA refuse de produire un
lien mort — d'où ce jalon plutôt que rien.

## Les champs sont versionnés

Les groupes de champs vivent en **JSON local** dans `acf-json/` du thème. ACF y
écrit et y lit tout seul dès que le dossier existe — aucun hook à poser.

Conséquence pratique : **un champ ajusté dans l'interface d'ACF modifie un
fichier du dépôt.** Il faut le committer, sinon l'ajustement ne suivra pas au
déploiement. C'est le prix de champs relisibles en diff.

## Ajouter une section

1. Créer `blocks/lcds-<section>/block.json` — nom `acf/lcds-<section>`, titre
   « LCDS — … », catégorie `lcds`, et une clé `acf` avec
   `"renderTemplate": "render.php"`.
2. Créer `blocks/lcds-<section>/render.php`. **Il ne doit que lire les champs et
   déléguer à un composant** de `components/` : réécrire le balisage le
   laisserait divorcer du composant, qui est mesuré au pixel contre la maquette
   et couvert par la campagne de QA.
3. Créer `acf-json/group_lcds_<section>.json`, avec pour localisation
   `[[{"param": "block", "operator": "==", "value": "acf/lcds-<section>"}]]`.

Rien à déclarer ailleurs : `inc/blocks.php` enregistre tout `block.json` trouvé
sous `blocks/`.

> Prévoir un indice pour l'éditeur quand le bloc est vide — les autres blocs
> émettent un `<p class="lcds-block-hint">` en mode aperçu. Sans lui, un bloc non
> rempli est invisible dans l'éditeur et le contributeur ne sait pas quoi faire.

## Deux pièges rencontrés

**`wp_insert_post()` attend des données échappées** et leur applique
`wp_unslash()` en interne. Sans `wp_slash()`, l'antislash des séquences
d'échappement du JSON de bloc est mangé — le `\n` séparant deux paragraphes
devenait un paragraphe contenant la lettre « n ».

**ACF exige, à côté de chaque valeur, une clé préfixée d'un `_`** portant la clé
du champ. Sans elle il ne sait pas à quel champ la valeur appartient, et les
répéteurs remontent vides. `bin/seed-homepage.php` résout ces clés depuis le
groupe de champs plutôt que de les écrire en dur.

## L'éditeur de blocs a été rétabli

Le thème le désactivait (`use_block_editor_for_post`). Le filtre a été retiré le
04/09/2026 : toute la contribution repose désormais sur des blocs.
