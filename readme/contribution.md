# Contribution du contenu

La page d'accueil se contribue depuis **un seul formulaire**, sous l'éditeur.
Ses sections sont les **layouts d'un champ de contenu flexible** ACF : un
contributeur les ajoute, les réordonne et les supprime au glisser-déposer.

C'est le modèle éprouvé sur 2bdm, avec une différence assumée : **les champs
restent versionnés en JSON local** dans `acf-json/`. Sur 2bdm ils ne vivent
qu'en base — un champ ajouté en préprod ne part pas en production et aucun diff
ne montre qui a changé quoi.

## Où modifier la page d'accueil

**Pages → Accueil → Modifier.** L'éditeur de blocs y est **coupé**, et son
contenu masqué : deux surfaces de saisie concurrentes sur la même page, c'est la
garantie qu'un contributeur remplira la mauvaise.

Coupé **page par page**, pas globalement : `inc/editor.php` ne neutralise que
les contenus listés par `lcds_acf_contributed_posts()`. Tout ce qui est du texte
libre — mentions légales, un futur article — s'écrit mieux en blocs qu'en
contenu flexible.

La page est créée par `bin/init.sh` au premier démarrage, garnie de la copie
relevée sur les maquettes, puis **désignée comme page d'accueil du site**
(Réglages → Lecture).

> L'amorçage est idempotent : une page déjà en place n'est **jamais** réécrite.
> Pour la recréer volontairement :
> `dwp eval-file bin/seed-homepage.php force`.

## Le catalogue de sections

| Section | Ce qu'elle porte |
| --- | --- |
| **Hero** | Visuel pleine largeur, carte d'appel |
| **Texte et galerie** | Étiquette, texte, bouton, rail de visuels défilable |
| **Accordéon** | Étiquette, entrées dépliables, bouton |
| **Parcours en étapes** | Étiquette, étapes numérotées avec durée et visuels |
| **Carrousel de cartes** | Étiquette, bouton, cartes inclinées révélant leur texte |
| **Informations pratiques** | Étiquette, visuel, entrées à icône |

**Le catalogue est celui de la page d'accueil et de personne d'autre.** Le
groupe est localisé par `page_type == front_page` : aucune de ces sections ne
peut atterrir sur une autre page. C'était le principal défaut du modèle
précédent en blocs, où les six sections étaient insérables partout.

### Ajouter une section

1. Créer `layouts/<nom>.php`. Il ne doit que **lire les sous-champs et déléguer
   à un composant** de `components/` : réécrire le balisage le laisserait
   divorcer du composant, qui est mesuré au pixel contre la maquette et couvert
   par la campagne de QA.
2. Ajouter le layout au champ `sections` de `acf-json/group_lcds_homepage.json`,
   avec `"name"` **égal au nom du fichier**.

`front-page.php` reste déclaratif : `get_row_layout()` donne le nom du gabarit,
il n'y a aucun `switch` à tenir à jour. Le nom passe par une allow-list bâtie
sur les fichiers présents — il vient de la base, il ne peut pas entrer tel quel
dans un chemin.

`tests/Unit/HomepageLayoutsTest.php` fait échouer la CI dans les deux sens : un
layout déclaré sans gabarit (section muette, sans erreur) et un gabarit sans
layout déclaré (code mort).

### Le titre h1

C'est un champ de la **page**, pas de sa première section. Les maquettes ne
prévoient aucun titre visible dans le hero : il est rendu **masqué
visuellement** — invisible à l'écran, lu par les moteurs et les lecteurs
d'écran. Sans lui, la page n'a aucun `h1`.

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

### Les cartes du carrousel s'inclinent par cycle de trois

Largeurs et inclinaisons alternent : **471,5 / 447,5 / 471,5** et
**+2,88° / 0 / −2,88°**, relevés au pixel sur le PDF. Le contributeur n'a rien à
régler — il ajoute des cartes, le cycle se poursuit.

Les inclinaisons ne sont pas retirées sous `prefers-reduced-motion` : cette
préférence concerne le mouvement, et une inclinaison fixe n'en est pas un.

### Les icônes des informations pratiques

Un contributeur choisit un nom — « Adresse », « Transports », « Information »,
« Horaires », « Contact » — jamais un fichier. La correspondance vit dans
`inc/enums/LcdsInfoIcon.php`, **source unique**, et la liste de
l'administration en est alimentée par un filtre `acf/load_field`.

`tests/Unit/InfoIconTest.php` vérifie que chaque cas pointe vers un composant
qui **existe sur le disque** : une icône sans fichier rendrait une entrée sans
glyphe, sans erreur.

> Les cinq glyphes sont des **redessins**. La maquette référence un jeu tiers
> dont le projet n'a pas les fichiers — à remplacer par les assets du designer.

### Ce qui suit le nombre d'entrées

- **Rail de visuels** : plus il y a de visuels, plus il défile. Rien ne plafonne
  leur nombre — voir [`../design/figma/nodes/12-207-accueil.md`](../design/figma/nodes/12-207-accueil.md).
- **Parcours** : la numérotation et la barre de progression se déduisent du
  nombre d'étapes. En ajouter une renumérote et rééchelonne tout.
- **Carrousel de cartes** : même règle que le rail de visuels, et le cycle
  d'inclinaison se poursuit indéfiniment.
- **Informations pratiques** : les filets se posent entre les entrées, jamais
  avant la première ni après la dernière.

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

## Le pied de page : « Réglages → Configuration »

Le pied de page est commun à toutes les pages : son contenu n'appartient à
aucune d'elles. Il vit donc dans une **page d'options** ACF, en sous-menu de
*Réglages* — trois blocs d'appel, le sur-titre et l'adresse, la mention de
copyright, le **visuel révélé** et son **cadrage**.

Sa navigation vient de deux emplacements de menu distincts, `footer-menu` et
`legal-menu` : un contributeur ne peut pas glisser « Mentions légales » au
milieu des pages du site — voir [`menus.md`](menus.md).

La structure du groupe est versionnée dans `acf-json/` comme le reste ; seules
les **valeurs** vivent en base, ce qui est leur place.

> **Le visuel révélé n'est pas décoratif.** C'est lui qu'on découvre en bas de
> page : sans lui, le pied de page reste simplement posé, sans animation. Son
> texte alternatif se saisit dans la médiathèque.

### Le cadrage du visuel révélé

Le visuel est rogné pour remplir toute la largeur : il ne montre qu'une bande de
la photo. **Laquelle relève du contenu, pas du gabarit** — une bouche en bas de
cadre et un visage en haut ne se cadrent pas pareil. Le contributeur choisit donc
« Haut de l'image », « Centre » ou « Bas de l'image ».

Les valeurs vivent dans `inc/enums/LcdsFocalPoint.php`, **source unique** du
libellé et du suffixe de classe (`is-focus-<valeur>`). La liste de
l'administration en est alimentée par un filtre `acf/load_field`.

L'`object-position` correspondante vit, elle, dans `partials/footer.scss` : ce
n'est pas une constante, elle compense le débord du visuel sous les coins
arrondis — voir
[`../design/figma/nodes/276-1063-footer.md`](../design/figma/nodes/276-1063-footer.md).

## Les champs sont versionnés, pas en base

C'est la différence assumée avec 2bdm, dont les neuf groupes et 165 champs
n'existent **que** comme lignes de base : là-bas, livrer un champ demande une
migration de base ; ici, il part avec le code.

Les groupes vivent en **JSON local** dans `acf-json/` du thème. ACF y écrit et y
lit tout seul dès que le dossier existe — aucun hook à poser. Le dossier est
dans `website/`, donc embarqué par l'artefact de déploiement.

Mesuré sur cet environnement :

```
load_json  : …/themes/lcds/acf-json   save_json : le même dossier
groupes en base : 0        champs en base : 0
groupe servi    : ID = 0, local = 'json'
```

`ID = 0` est le point qui compte : le groupe rendu ne vient d'aucune ligne de
base. Une assertion de `bin/qa-front.sh` le vérifie à chaque campagne.

### Ce qui peut quand même diverger

**Enregistrer un groupe depuis l'interface d'ACF crée une copie en base** — et
réécrit le JSON. Vérifié : tant que le fichier est là et pas plus ancien, ACF
continue de servir le fichier, la copie ne prend pas la main. Elle ne devient
dangereuse que si le JSON n'arrive pas, ou arrive périmé.

Deux garde-fous :

1. **L'interface de gestion des groupes est masquée hors développement**
   (`acf/settings/show_admin`, `inc/acf.php`). Vérifié : visible en
   `development`, masquée en `staging` et en `production`. La saisie des valeurs
   par les contributeurs n'est pas touchée — seule la modification de la
   structure l'est.
2. **Les clés propres à la machine sont retirées du fichier après écriture.**
   ACF pose `local_file`, un **chemin absolu**, quand il charge un groupe, et le
   réécrit à l'enregistrement. Rien ne casse — ACF l'écrase à chaque
   chargement — mais le fichier devient différent d'une machine à l'autre sans
   qu'aucun champ n'ait bougé, ce qui ruine la relecture en diff.
   `tests/Unit/FieldConfigTest.php` fait échouer la CI si un tel chemin est
   committé.

> Le filtre documenté `acf/pre_save_json_file` **ne convient pas** : il ne
> concerne que les types de contenu ACF. Pour un groupe de champs,
> `update_field_group()` appelle `save_file()` en direct et le court-circuite —
> vérifié, la clé survivait. Le nettoyage est donc accroché **après** l'écriture.

**Un champ ajusté dans l'interface modifie un fichier du dépôt.** Il faut le
committer, sinon l'ajustement ne suivra pas au déploiement. C'est le prix de
champs relisibles en diff.

## Deux pièges rencontrés

**ACF exige, à côté de chaque valeur, une clé préfixée d'un `_`** portant la clé
du champ. Sans elle il ne sait pas à quel champ la valeur appartient, et les
répéteurs remontent vides. `bin/seed-homepage.php` résout ces clés depuis le
groupe de champs plutôt que de les écrire en dur.

**Le champ de contenu flexible range la LISTE de ses layouts** dans la clé
`sections`, puis chaque sous-valeur sous `sections_<index>_<nom>`. Sans cette
liste, ACF ne sait pas combien de rangées lire et le champ remonte vide.

L'amorçage fait aussi **table rase** des métadonnées `sections*` avant de
réécrire : un réamorçage après suppression d'une section laisserait sinon ses
valeurs orphelines en base, et ACF les remonterait sur la section qui a pris sa
place.

## L'éditeur de blocs, page par page

Le thème le coupait globalement, puis l'avait rétabli pour la contribution en
blocs. Depuis la bascule en contenu flexible du 04/09/2026, il est coupé
**uniquement** sur les contenus listés par `lcds_acf_contributed_posts()`
(`inc/editor.php`) — la page d'accueil pour l'instant.

Une assertion de `bin/qa-front.sh` vérifie les deux sens : coupé sur la page
d'accueil, **actif ailleurs**. Élargir le filtre à tout le site doit rester une
décision, pas un effet de bord.
