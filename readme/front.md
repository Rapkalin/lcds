# Socle front

## Les tokens viennent de Figma, pas du CSS

`assets/styles/basics/variables.scss` est la transposition des **variables de
bibliothèque** du fichier Figma `LCDS | UI`. Règle : une teinte ou une taille
absente de ce fichier est absente de la maquette. On l'ajoute d'abord côté
design, jamais directement ici.

| | Valeur |
| --- | --- |
| Bleu | `#00387A` |
| Turquoise | `#048B8C` |
| Orange | `#E25304` |
| Fond clair | `#F2F8FF` |

Toute la maquette tient sur **quatre styles de texte** — vérifié en recoupant
les hauteurs de bloc relevées dans Figma, toutes multiples de la hauteur de
ligne correspondante :

| Style | Taille / interligne | Graisse |
| --- | --- | --- |
| H2 | 48 / 1.2 | Sligoil Micro |
| H3 | 24 / 1.2 | Sligoil Micro |
| Paragraphe | 16 / 1.4 | Inter SemiBold |
| CTA | 13 / 1, interlettrage 8 % | Inter Medium |

> **Piège des interlignes annoncés par Figma.** Il affiche 1.2 et 1.4, mais
> **arrondit au pixel entier** l'interligne rendu — ce que CSS ne fait pas.
> Mesuré sur les PDF : **58,000** pour un titre de 48 (et non 57,6) et
> **22,000** pour un paragraphe de 16 (et non 22,4). Les rapports retenus,
> `1.2083333` et `1.375`, restituent ces entiers aux tailles de la maquette.
> Reprendre 1.2 et 1.4 décalait toute la page de 3px.
>
> Corollaire : à une taille de police non prévue par la maquette, aucun rapport
> ne peut reproduire l'arrondi de Figma. Le décalage y sera infra-pixel.

Deux transpositions plutôt que des recopies :

- **L'interlettrage est en `em`.** Figma affiche `1.04px`, ce qui n'est vrai qu'à
  13px ; la variable de bibliothèque dit 8 %. `0.08em` suit n'importe quelle
  taille.
- **Les hauteurs de ligne sont sans unité** (`1.2`, `1.4`) : elles suivent la
  taille de police au lieu de la contredire.

## Conteneur centré : `$content-outer`, pas `$content-width`

`box-sizing: border-box` fait entrer le rembourrage **dans** la largeur. Un
conteneur à `max-width: $content-width` avec `padding: 0 20px` ne laisse donc
que 1078px de contenu, et décale tout de 20px vers l'intérieur.

`$content-outer` ajoute les deux marges à la largeur utile : à 1440 le contenu
retrouve ses 1118px et ses marges de 161px. Constaté sur la section des
traitements — colonne à 642 au lieu de 666, boutons à 1207 au lieu de 1227.
Deux assertions de [`qa.md`](qa.md) verrouillent ces cotes.

## `box-sizing: border-box`, sans exception

Déclaré sur `*` dans `basics/general.scss`. **Ce n'est pas une préférence de
style** : par défaut une largeur ou un `aspect-ratio` s'applique à la boîte de
contenu, et le rembourrage s'ajoute par-dessus. Toute cote relevée sur la
maquette est alors fausse de la valeur du rembourrage.

Constaté sur le hero : 864px rendus pour 900 dessinés, et sa carte à 347 pour
327. Trois assertions de [`qa.md`](qa.md) verrouillent désormais ces cotes.

## Grille

Gouttière de **12px**, constante partout dans la maquette. Le contenu fait
**1118px** dans un cadre de 1440, soit des marges de **161px** — sauf l'en-tête,
qui respire moins (**48px**). Les blocs de **666px** se décomposent en
`101 + 12 + 553` : colonne du numéro, gouttière, colonne de texte.

Le module de **52px** revient partout : boutons ronds, pastilles, piste de
progression des carrousels.

> **Points de rupture.** `1024px` replie l'en-tête en menu burger **et** met les
> sections en une seule colonne ; `680px` porte les derniers ajustements
> (visuels pleine largeur, écarts resserrés). Aucune maquette mobile n'existe :
> tout ce qui suit est une proposition, à revalider quand elles arriveront.

## Ce qui s'adapte, et pourquoi

Trois grandeurs sont fluides. Toutes valent **exactement la valeur dessinée à
1440 de large** : les cotes relevées sur la maquette restent donc justes, et les
assertions de [`qa.md`](qa.md) qui les vérifient au pixel n'ont pas bougé.

| Grandeur | À 1440 | Au plancher |
| --- | --- | --- |
| `$fs-h2` | 48px | 32px (≤ 480) |
| `$fs-h3` | 24px | 20px (≤ 480) |
| `$section-padding` | 128px | 64px (≤ 720) |

**Pourquoi les titres.** Un H2 figé à 48px occupait trois lignes et presque tout
l'écran à 500px de large. La bibliothèque Figma laisse entendre qu'un style
`H2/mobile` est prévu : **remplacer ces paliers par ses valeurs** dès qu'il sera
fourni.

**Pourquoi le retrait de section.** Deux sections voisines cumulaient 256px de
vide, disproportionné sur un écran étroit.

Le corps de texte, lui, reste à **16px partout** : c'est déjà le plancher de
lisibilité.

## Le hero est borné par la hauteur de la vue

`max-height: min(900px, 100svh)`. Le second plafond n'est pas cosmétique : sans
lui le hero gardait ses 900px sur un écran plus bas, et **la carte « Prendre
RDV » passait sous la ligne de flottaison** — mesuré à 63px de coupe sur une vue
de 813px, 163px sur une vue de 713px, sur toutes les tailles d'écran portable
courantes.

`svh` et non `vh` : sur mobile, la barre d'adresse ne doit pas rogner la carte.

> Corollaire pour les assertions : la hauteur du hero dépend de la vue, donc les
> positions **absolues** de tout ce qui suit aussi. Mesurer depuis le haut de la
> section concernée, jamais depuis celui du document.

## Grilles : toujours `minmax(0, Nfr)`

Une piste `fr` ne descend jamais sous la largeur minimale de son contenu. Avec
des enfants à largeur fixe — les visuels du parcours, par exemple — la colonne
refuse de se réduire et **les colonnes voisines se compriment à sa place**, en
silence. `minmax(0, …)` rend le comportement explicite.



## Polices — pas encore auto-hébergées

| Police | Rôle | Licence |
| --- | --- | --- |
| [Sligoil](https://velvetyne.fr/fonts/sligoil/) (coupe *Micro*) | Titres | Libre, Velvetyne — usage commercial autorisé, redistribution sous la même licence |
| [Inter](https://github.com/rsms/inter) | Textes | SIL OFL 1.1 |

Les deux sont libres : auto-hébergement en `@font-face`, sans licence à acheter
et **sans toucher à la CSP** (pas de Google Fonts). Tant que les fichiers
manquent, seule la pile de secours s'applique.

**Sligoil est une police à chasse fixe** (vérifié sur le rendu Figma : titres et
numéros d'étape monospacés, zéro barré). Son repli doit l'être aussi, sinon la
mise en page saute avant le chargement.

> Figma annonce un poids `90` pour « Sligoil Micro ». C'est le nom de la coupe,
> pas un poids CSS — la famille compte *Micro*, *Micro Medium* et *Micro Bold*
> depuis juin 2025, et la maquette utilise la régulière. À confirmer sur le
> fichier de police une fois installé.

### Ajouter les fichiers

**Ne pas ajouter de règle `asset/resource` pour les polices dans
`webpack.config.js`.** Webpack 5 les gère déjà, en leur donnant un nom dérivé de
leur contenu. Ce hachage est indispensable : `.htaccess` sert `dist/` avec un
`Expires` à un mois, donc un nom de fichier fixe figerait la police chez les
visiteurs déjà venus. Vérifié dans les deux sens : le build passe sans règle et
émet `<hash>.woff2` ; avec une règle `[name][ext]`, il émet un nom stable et
perd l'invalidation.

Déposer les `.woff2` dans `assets/fonts/`, déclarer les `@font-face` dans un
partiel de `basics/`, et l'importer depuis `app.scss`.

## Invalidation du cache des assets

`main.css` et `main.js` gardent un nom fixe et sont servis avec le même
`Expires` d'un mois. WordPress n'ajoutait que `?ver=` suivi de **sa propre
version** : une mise en production ne parvenait donc pas aux visiteurs déjà
venus, jusqu'à trente jours.

`theme_lcds_asset_version()` (dans `inc/setup.php`) dérive la version de la date
de modification du fichier. Tout nouvel asset compilé mis en file doit passer
par elle.

## Contenu de démonstration

Le contenu de la page d'accueil est **contribuable** par un champ de contenu
flexible — voir [`contribution.md`](contribution.md). Les gabarits ne portent
plus aucune donnée : `front-page.php` boucle sur les rangées et délègue à
`layouts/<nom>.php`, qui délègue au composant.

`bin/seed-demo.sh` reste utile en local : il importe dans la médiathèque les
photos **extraites des PDF de maquette**, puis réamorce la page d'accueil pour
les y placer — sans quoi l'aperçu de l'éditeur montrerait des cadres vides. Il
**écrase donc le contenu saisi** dans l'éditeur.

```bash
bin/seed-demo.sh            # importe, ne refait rien si déjà fait
bin/seed-demo.sh --force    # supprime les précédents et recommence
```

Prérequis : `pdfimages` (poppler) et `sips` (fourni par macOS), plus les
maquettes — dossier réglable par `LCDS_MOCKUPS_DIR`.

Le script est **ignoré par git** (`.gitignore`) : il dépend de maquettes qui ne
sont pas dans le dépôt et ne sert qu'en local.

## Un seul carrousel pour deux sections

La maquette dessine les contrôles du carrousel Technologies **strictement
identiques** à ceux de la galerie d'intro : piste de 214 à x=161, deux boutons
de 52 alignés sur 1279. Le composant `carousel` accepte donc, par élément, du
**balisage déjà produit** (`content`) en plus du chemin « simples visuels », plus
une hauteur de rail et une inclinaison. Écrire un second composant aurait
dupliqué le rail, les contrôles et leur câblage JavaScript.

`lcds_capture()` (`inc/template.php`) sert à ça : `get_template_part()` écrit sur
la sortie et n'offre aucun moyen de récupérer le résultat, or le rail a besoin du
balisage de ses cartes sous forme de chaîne.

## Le rail se tire à la souris

Les flèches ne sont plus le seul moyen d'avancer : le rail se **glisse** au
pointeur. `pointerdown` sur le rail note la position, `pointermove` reporte
l'écart sur `scrollLeft`, `pointerup` rend la main.

Trois décisions valent d'être écrites, chacune ayant coûté une reprise :

- **Souris seulement** (`event.pointerType !== "touch"`). Au doigt, le
  défilement natif porte déjà l'inertie et le rebond ; capter le geste tactile
  fait surtout perdre le défilement **vertical** de la page dès que le doigt
  part de travers.
- **Pas de `setPointerCapture`.** Elle redirige tous les évènements vers le
  rail, y compris ceux destinés au bouton d'une carte : le clic n'arrivait plus.
  `pointermove` et `pointerup` sont donc écoutés sur le **document**, ce qui
  couvre aussi le relâchement hors du rail. Un `event.buttons === 0` au retour
  dans la fenêtre rattrape un bouton relâché dehors.
- **Un seuil de 6px** avant que l'appui devienne un glissement, et le `click`
  qui suit un glissement est **avalé en capture**. Sans le seuil, un tremblement
  de souris empêchait d'ouvrir une carte ; sans l'avalement, un glissement
  terminé sur un bouton en ouvrait le panneau.

Le glissement **s'ajoute**, il ne remplace rien : flèches, clavier, molette et
geste tactile restent les chemins d'origine. C'est ce qui satisfait le critère
WCAG 2.5.7, qui exige une alternative à tout geste de glissement.

Le curseur « main » n'apparaît que si le rail déborde réellement de la vue
(`carousel--draggable`, posée par le script) : sur un rail qui tient en entier,
il promettrait un geste sans effet.

## Le parcours avance par étapes, jamais entre deux

La section « parcours de soin » est haute de plusieurs écrans, la vue reste
collée et le rail se décale. **Deux mécanismes** cohabitent, et ils répondent à
deux questions différentes.

### Un cran de molette, une étape — le rythme est un CHRONO

Le geste de molette est **confisqué** tant que la vue épinglée occupe l'écran et
qu'il reste une étape dans son sens : la page ne bouge plus de son fait, c'est
le script qui la porte d'une étape à la suivante. Deux effets voulus :

- **seul le SENS du geste compte, pas son amplitude** — une roulette lâchée d'un
  coup avance d'une étape, pas de quatre ;
- **tout ce qui arrive pendant la cadence est absorbé** (`JOURNEY_CADENCE`,
  600ms — elle couvre la transition CSS de 450ms), donc plusieurs crans
  rapprochés valent un seul.

C'est **un chrono qui règle le rythme, plus une distance**. Chaque carte est donc
tenue le même temps, la dernière comprise — ce que les réglages de hauteur ne
parvenaient pas à obtenir.

#### Trois constantes, et pourquoi il en faut trois

Une cadence fixe ne suffit pas, et c'est le premier défaut qu'elle a produit :
**deux étapes passaient d'un seul geste.**

Un geste de pavé tactile — et une roulette sous macOS — n'émet pas un évènement
mais une **traîne**, qui continue près d'une seconde après que le doigt a quitté
la surface. À l'expiration des 600ms, l'inertie encore vivante déclenchait une
seconde bascule. D'où :

| | rôle |
| --- | --- |
| `JOURNEY_CADENCE` (600ms) | plancher après une bascule ; couvre la transition CSS |
| `JOURNEY_REPOS` (150ms) | **silence** au-delà duquel un évènement ouvre un geste NEUF |
| `JOURNEY_PLAFOND` (1600ms) | sortie de secours, comptée depuis la dernière bascule |

Un geste neuf se reconnaît à un **silence qui le précède**. Tant que les
évènements se suivent, c'est le même geste qui vit sur son inertie, et il a déjà
eu son étape — quelle que soit la durée de sa traîne. C'est ce qui rend le
blocage **catégorique** et non seulement probable : une cadence fixe, elle,
finissait par expirer sous une traîne d'une seconde et laissait passer une
seconde carte.

Le plafond n'est pas décoratif : sans lui, un défilement **continu** — deux
doigts qui ne se lèvent pas — n'ouvrirait jamais de geste neuf et la section
deviendrait un cul-de-sac. Avec lui, un tel défilement avance d'une étape toutes
les 1,6s.

Les deux comportements sont éprouvés : exigence de geste neuf retirée, une traîne
d'une seconde vaut `650px de plus`, soit une seconde étape. Plafond retiré, un
défilement continu de deux secondes n'avance plus du tout (`0px de plus`).

#### L'arrivée bloque sur le bord franchi, puis rend la main

Le geste qui amène la page jusqu'à la section n'est **pas** confisqué — il ne
peut pas l'être, la section n'étant pas encore collée. Deux choses s'ensuivaient,
et il a fallu les corriger l'une après l'autre.

**Sa traîne emportait la carte d'entrée.** Elle arrive quand la section est
collée, trouvait un verrou libre et faisait aussitôt basculer une étape : on
démarrait sur la deuxième carte. Le premier geste vu à l'intérieur est donc
absorbé sans rien avancer, et la cadence est armée comme après une bascule.

**Et le dépassement était entériné.** Chrome **anime** le défilement de molette :
la page continue de glisser après le dernier évènement, sans qu'aucun ne soit là
pour l'arrêter. S'ancrer « au palier le plus proche » revenait donc à accepter la
glissade. L'ancrage se fait sur le **bord franchi** — la première étape si l'on
descend, la dernière si l'on remonte — ce qui ramène la page en arrière si la
glissade a dépassé.

C'est symétrique, et pas par élégance : on arrive aussi **par le bas**, et
c'était alors la dernière carte qui était sautée.

La reconnaissance de l'arrivée est **bornée à une étape du bord**. Au-delà, on
n'arrive pas, on est déjà dedans : un visiteur amené au milieu au clavier ou à la
barre de défilement ne doit pas être ramené en arrière de plusieurs écrans à son
premier coup de molette.

Les quatre règles sont éprouvées, et chaque épreuve reproduit un défaut réel :

| ce qu'on retire | ce qui se passe |
| --- | --- |
| l'ancrage sur le bord | la page reste à `650px`, deuxième carte — le défaut constaté |
| l'exigence de geste neuf | la traîne d'entrée emporte `650px` |
| la borne d'une étape | un visiteur au milieu est ramené à la première carte |
| la détection d'arrivée | la page reste où la glissade l'a laissée, `1301px` |

#### Les cartes de bout marquent un arrêt

Le même défaut se rejoue à la **sortie**, et il fallait la même réponse. La
dernière carte est posée par un geste ; la traîne de ce geste emportait aussitôt
la page hors de la section, et la carte n'apparaissait qu'un instant. Elle
marque donc un **arrêt** : il faut un second geste pour sortir, comme il en faut
un pour avancer.

C'est armé par l'ancrage lui-même — `arret` vaut vrai dès qu'on se pose sur la
première ou la dernière étape, que ce soit par une arrivée ou par une bascule.
L'arrivée et la sortie tiennent donc par une seule règle.

L'arrêt **retombe dès que la sortie est accordée**. Sans cela, les évènements
suivants du même geste rattrapaient la page en route et la bloquaient à
mi-chemin — et sans le plafond, un défilement continu n'en sortait jamais. Les
deux sont éprouvés : arrêt jamais relâché, `un second geste rend la main` passe
au rouge dans les deux sens.

> **C'est du détournement de défilement, et il faut le savoir.** À la molette,
> un visiteur doit parcourir les six cartes pour passer la section. Quatre
> sorties existent, et aucune n'est un accident :
>
> - **le clavier n'est pas touché** — et `Page suivante` (≈ 810px sur un écran
>   de 900) vaut à peu près une étape (650px), donc l'expérience est la même ;
>   c'est aussi la sortie de secours si un arrêt de bout se coinçait, ce que le
>   plafond interdit déjà ;
> - **le tactile n'est pas touché** : sur mobile, le défilement natif garde
>   l'inertie et le rebond, et c'est le chemin de repli ci-dessous qui joue ;
> - **la barre de défilement** reste libre ;
> - **sous `prefers-reduced-motion`, la section n'est pas épinglée du tout** :
>   rien n'est confisqué, les étapes s'empilent.
>
> Le verrou est un **minuteur**, jamais l'attente d'un évènement qui pourrait ne
> pas venir : il ne peut pas se coincer. Et aux deux bouts — premier palier vers
> le haut, dernier vers le bas — le geste n'est pas confisqué, sans quoi on
> n'entrerait jamais dans la section et on n'en sortirait plus.

### La hauteur — désormais le chemin de repli

```
height: calc(100svh + (var(--journey-steps) - 1) * var(--journey-step-scroll))
--journey-step-scroll: 80svh
```

Elle valait un écran **par étape** — six écrans de défilement pour six cartes,
donc un glissement très lent. Elle vaut 5 écrans : un pour la vue collée, quatre
pour les cinq transitions.

Depuis le verrou, cette hauteur ne règle plus le rythme à la molette — elle
règle **la distance dont la page saute** à chaque étape (invisible, la vue étant
épinglée) et le comportement des chemins que le verrou ne touche pas : clavier,
barre de défilement, tactile. 80svh valent 650px de course par étape sur un
écran de 900, soit à peu près une pression de `Page suivante` : c'est ce qui
aligne le clavier sur la molette.

**L'arrondi.** Le script publie la fraction **brute** du défilement ; le CSS la
ramène à l'étape la plus proche. Avant, le rail se posait n'importe où : la fin
d'une étape et le début de la suivante se partageaient l'écran avec un grand
vide entre les deux — leur colonne de texte est à droite de leur grille, d'où
l'impression de cartes « très écartées ».

```css
@supports (width: round(nearest, 1px, 1px)) {
  .journey { --journey-step-index: round(nearest, var(--journey-progress) * (var(--journey-steps) - 1), 1); }
}
```

Deux points de méthode :

- **En CSS et non dans le script**, pour que la translation ET le remplissage de
  la barre lisent le même nombre arrondi. Un arrondi côté script aurait dû être
  refait à l'identique pour chacun.
- **Sous `@supports` et non en repli de cascade.** `round()` ne s'évalue qu'au
  calcul : une propriété personnalisée qui l'emploie reste valide à l'analyse et
  n'écarte donc pas la déclaration précédente. Sans cette garde, un moteur qui
  l'ignore n'aurait **plus de translation du tout** et cinq étapes deviendraient
  inatteignables.

> Ce qui reste supposé : le comportement des **traînes plus longues que 1,6s**.
> Le plafond finit par céder, donc un geste dont l'inertie dépasserait cette
> durée vaudrait deux étapes. Aucun périphérique observé ne va aussi loin — la
> mesure porte sur une traîne d'une seconde — mais la borne existe, et c'est
> `JOURNEY_PLAFOND` qui la déplace si le cas se présente.
>
> Supposé aussi : **l'amplitude de la glissade de Chrome**. La campagne la rejoue
> à 0,6 étape, ce qu'un évènement synthétisé ne peut pas produire lui-même. Une
> glissade dépassant une étape entière ne serait plus reconnue comme une arrivée
> et laisserait la première carte de côté ; c'est la borne de `<= 1` étape qui se
> desserre alors.

## L'en-tête suit le défilement

`position: sticky; top: 0` par défaut, `fixed` sur une page qui porte un hero.

Le choix de `sticky` pour le cas général n'est pas cosmétique : l'en-tête reste
**dans le flux**, donc il réserve lui-même sa hauteur. En `fixed` partout, il
aurait fallu rendre cette hauteur à la page par un nombre écrit à la main —
faux dès que l'en-tête revient à la ligne, ce qu'il fait à fort grossissement de
texte. Le cas `hero` n'a pas ce problème : le recouvrement de la photo est
justement voulu.

`--header-height` est publiée sur `:root` par le script, parce qu'aucune
addition de jetons ne la donne. La vue épinglée du parcours s'en sert : elle est
collée au même bord que l'en-tête, et son retrait se resserre sous 128px sur un
écran bas — l'étiquette passait dessous.

### Aucun fond au défilement — arbitré

**L'en-tête reste transparent sur toute la page.** C'est la maquette, et c'est
la décision prise : les pastilles blanches des liens et le bouton d'action
suffisent à les rendre lisibles sur ce qui défile derrière.

Un fond blanc apparaissant une fois le hero passé avait été proposé, puis
écarté. Deux assertions gardent la décision — le fond de l'en-tête **et** celui
de son pseudo-élément doivent rester transparents. Elles ont été éprouvées dans
les deux sens, un fond réintroduit sur l'un comme sur l'autre les faisant
passer au rouge.

> Si la question se rouvre, sachez que le fond doit être peint sur un
> **pseudo-élément** et non sur l'en-tête dès qu'un `backdrop-filter` entre en
> jeu : ce filtre fait de son élément le bloc conteneur de ses descendants en
> `fixed`, et le panneau de menu mobile est précisément un
> `position: fixed; inset: 0`. Posé sur l'en-tête, il le rabattait à la taille
> de la barre.

## Rien sous un visuel : le liseré des cartes inclinées

Les cartes du carrousel Technologies sont **inclinées**. Leur découpage arrondi
devient donc un contour anticrénelé, et le pixel de bord mélange le visuel avec
**ce qui est peint dessous**. L'aplat de repli `background: $blue` de
`.tech-card` y dessinait un liseré bleu foncé d'un pixel, dont l'intensité varie
le long du bord puisque la couverture varie — ce qui se lit comme un pointillé.

Mesuré sur la construction réelle (feuille compilée, balisage servi, photo de la
médiathèque), pivotée de 2,88° : l'écart du pixel de bord au mélange légitime
visuel/fond passe de **18,8 à 2,9** de médiane quand l'aplat n'est plus sous le
visuel. Les pires cas parlent d'eux-mêmes : bord `#9CAEBA` — bleu froid — alors
que l'intérieur immédiat est un olive `#726410`.

L'aplat n'est pas supprimé, il est **déplacé** sur `tech-card--plain`, posée par
le composant quand il n'y a pas de visuel. Une carte sans photo garde donc son
fond lisible, et une carte avec photo n'a plus rien dessous.

> Ce qui reste supposé : le **crénelage du découpage lui-même**. Il n'a pas pu
> être reproduit — en rendu logiciel, le bord d'une carte inclinée est
> anticrénelé à 94 % des colonnes, avec ou sans masque. Si un escalier subsiste
> sur une machine donnée, c'est une affaire de composition GPU, hors de portée
> de la campagne.

## L'animation de la navigation

Reprise de **floema.com** (référence client) : l'élément survolé **écarte ses
voisins**. C'est du CSS pur, aucun JavaScript.

```
au repos : transition: margin .3s cubic-bezier(.19, 1, .22, 1)      easeOutExpo
au survol: margin: 0 20px
           transition: margin .5s cubic-bezier(.175, .885, .32, 1.275)  easeOutBack
```

Deux courbes distinctes selon le sens : la sortie se pose vite et net, l'entrée
**dépasse légèrement avant de revenir**. C'est ce dépassement qui donne le
ressort — une seule courbe pour les deux sens rend l'effet plat.

Une **zone de survol débordant de 20px** sur les quatre côtés (`::before`) fait
partir l'écartement *avant* que le curseur touche la pastille. Sans elle, l'effet
se déclenche trop tard et paraît saccadé. Elle agrandit aussi la cible, ce qui
ne nuit jamais.

La page courante reste écartée en permanence, et deux règles empêchent l'écart
de **doubler** entre elle et une voisine survolée.

### Le collet entre deux pastilles

Le blanc des pastilles **ne vient pas de leur fond**. Il vient d'un seul `<path>`
peint derrière les liens, qui trace d'un trait les pastilles ET les collets qui
les relient.

C'est la seule façon d'obtenir la continuité demandée. Un chaînon posé dans
l'écart — ce qu'on avait — reste un second fond, et deux fonds qui se touchent
laissent toujours une couture à leur rencontre. Ici il n'y a qu'une silhouette,
donc rien à raccorder.

#### La géométrie, relevée sur la référence

Sur une rangée de 36,80 de haut pour un arrondi de 12,51, le pont de floema.com
mesure **0,96 de large sur 20,23 de haut**, ses arêtes creusées de 0,40. Ce qui
compte n'est pas ces chiffres mais ce qu'ils impliquent : le collet s'accroche à
**19,4° sur l'arrondi**, donc il en **mange la plus grande part**, et ses arêtes
repartent **tangentiellement**, sans angle.

C'est cet angle, et non une largeur de chaînon, qui fixe la taille de guêpe.
Mesuré chez nous, à `isPointInFill` : **61 % de la hauteur de la rangée au
repos**, contre 55 % pour la référence — l'écart vient de nos proportions, notre
arrondi vaut 0,28 de la hauteur là où le leur vaut 0,34.

#### Le plafond des poignées

Les poignées de Bézier valent la **moitié de l'écart** — rapport relevé sur la
référence — puis **plafonnent** à la moitié de l'arrondi. Sans ce plafond, un
écart de 20px les enverrait si loin que les deux arêtes se croiseraient et
**fermeraient le collet** : vérifié en le portant à 3, le collet tombe de 61 % à
12 % et la campagne passe au rouge.

Avec le plafond, le collet s'affine en s'étirant — 61 % au repos, **43 % à
20px** d'écart — sans jamais rompre. C'est le filament de la référence.

#### Ce que la campagne vérifie

Pas une largeur : la **silhouette**. `isPointInFill` répond exactement, peint ou
non, là où une lecture de style ne dirait rien du dessin obtenu. La campagne
balaie la médiane de la barre et compte les trous — zéro au repos, zéro une fois
l'écart forcé à 20px.

Elle force cet écart elle-même : la campagne neutralise le mouvement, or c'est
justement l'écart ouvert qui met le générateur à l'épreuve.

#### Trois pièges rencontrés

- **La forme est positionnée, la liste ne l'était pas.** L'ordre de peinture
  mettait alors la forme **au-dessus des libellés**. Ça tenait par accident au
  `position: relative` des liens ; c'est désormais posé sur la liste.
- **La forme RESTE sous `prefers-reduced-motion`** : elle ne bouge pas, elle
  peint. La retirer aurait rendu la barre invérifiable par la campagne, qui
  force ce réglage. Ce qui disparaît, c'est l'écartement — donc le seul signal
  de survol, remplacé par un **soulignement**.
- **Sous le point de rupture, aucun tracé** : la navigation devient un panneau
  vertical, il n'y a plus de rangée. Le tracé est également abandonné si les
  pastilles ne sont plus alignées — à 200 % de taille de texte elles passent à
  la ligne, et une barre supposée unique peindrait un bloc en travers du menu.

Sans JavaScript, la classe `site-nav--shaped` n'est jamais posée et les liens
gardent leur fond blanc : l'en-tête est transparent au-dessus de la photo du
hero, ils y seraient sinon illisibles.

### La dernière entrée ne s'écarte que vers la gauche

De ce côté elle se comporte comme n'importe quelle autre : elle déplace sa
voisine et ouvre son collet. De l'autre elle borde le bouton « Prendre RDV »,
qui **n'appartient pas au menu** — il vit sur son propre emplacement, flotte à
côté. Un écart à droite l'aurait décollé du menu alors qu'il n'en fait pas
partie.

**Le bouton ne bouge pas pour autant.** L'en-tête est en `space-between` : la
navigation s'allonge donc vers la GAUCHE, et son bord droit reste où il est.
Mesuré au survol de « Contact » :

| | |
| --- | --- |
| Écart ouvert à sa gauche | **20,0px** |
| Écart vers « Prendre RDV » | 12,0 → **12,0** — inchangé |
| Position de « Prendre RDV » | x 1258,2 → **1258,2** |
| Collet ouvert à sa gauche | **12,6 sur 29,0**, soit 43 % |

### Deux écarts assumés avec la référence

- **`:focus-visible` en plus du survol.** floema.com ne prévoit que `:hover` —
  vérifié, zéro règle de focus sur ces boutons — donc un utilisateur au clavier
  n'y voit jamais l'animation.
- **Suspendue sous `prefers-reduced-motion`.** L'écartement déplace les voisins :
  c'est du mouvement. Le survol est alors signalé par un soulignement.

### L'entrée courante porte une puce

L'entrée du menu qui correspond à la page affichée porte une **puce** avant son
libellé — même diamètre que la puce d'une étiquette de section (`tag__dot`).

**Sa couleur se règle dans « Réglages → Configuration »**, entre « Vert » et
« Rouge », et vaut « Rouge » par défaut. C'est un choix de contribution, pas une
constante de thème.

Le « rouge » du client est l'`orange` du système de design (`#E25304`) : la
correspondance existait déjà dans `LcdsDotColor`, dont le libellé dit « Rouge »
là où la valeur enregistrée dit `orange`. **Aucun jeton n'a été ajouté** — la
règle est qu'une teinte absente de la bibliothèque Figma s'ajoute côté design
d'abord.

La correspondance valeur → teinte vit dans **une seule** carte Sass,
`$dot-colors` (`basics/variables.scss`). `tag.scss` et `header.scss` la
parcourent : elle était écrite en double, et une couleur ajoutée à l'enum devait
alors être déclarée deux fois.

Trois décisions de mise en œuvre, chacune pour une raison :

- **`::after` avec `order: -1`**, et non `::before` : celui-ci porte déjà la zone
  de survol étendue de 20px, en position absolue.
- **`display: flex` posé sur cette entrée SEULEMENT.** Sur toutes, il aurait
  changé la boîte de chacune — et c'est cette boîte que le tracé de la barre
  mesure.
- **La puce occupe de la place** (12 de puce, 8 d'écart) plutôt que d'être
  superposée en absolu. Une assertion mesure ces 20px : une puce qui ne pousse
  rien passerait toutes les autres.

Ce n'est **pas le seul repère** : WordPress pose `aria-current="page"` sur ce
lien, et l'entrée reste écartée en permanence. Un repère de couleur seule aurait
échoué au WCAG 1.4.1.

## L'ouverture des panneaux d'accordéon

Le panneau reste un vrai `hidden` — c'est ce qui le retire de l'arbre
d'accessibilité et de l'ordre de tabulation, et rien ne le remplace. Ce qui a été
ajouté, c'est une transition qui **survit à la bascule de `display`** :

```css
transition: grid-template-rows .3s, opacity .25s, display .3s allow-discrete;
```

Quatre points, tous nécessaires :

- **`allow-discrete`** : `display` est une propriété discrète, elle saute d'un
  coup. Sans elle la fermeture n'a pas lieu du tout — le panneau disparaît avant
  d'avoir pu s'effacer.
- **`@starting-style`** fournit l'état de départ à l'ouverture : un élément qui
  vient de quitter `display: none` n'a pas d'ancienne valeur à interpoler.
- **`grid-template-rows: 0fr → 1fr`** plutôt qu'une hauteur : celle du contenu
  n'est pas connue, et `height: auto` ne s'anime pas. L'enfant a besoin de
  `min-height: 0` pour que la ligne puisse réellement se refermer.
- **`display: grid` sur l'état OUVERT seulement.** Déclaré sur les deux, il
  l'emporte sur le `display: none` de la feuille du navigateur et le panneau ne
  se ferme plus jamais — éprouvé, l'assertion passe au rouge.

Sans prise en charge d'`allow-discrete`, tout ceci est ignoré et la bascule reste
instantanée : c'est le comportement d'avant. Et sous `prefers-reduced-motion`,
la transition est neutralisée — un contenu qui s'ouvre et pousse ce qui suit est
du mouvement.

## Deux relevés qui ont corrigé une interprétation

Deux retours de recette portaient sur des détails qu'on aurait pu « corriger » au
jugé. Dans les deux cas le **PDF de maquette** a tranché, lu au pixel avec le
décodeur PNG de `bin/qa` — `sips` convertit le PDF, le reste est de l'arithmétique.

### Les boutons d'action portent tous du texte blanc

`.cta--outline` était la seule variante à texte bleu — c'est ce qui a été
remonté. Elle n'a plus de style propre : même pastille bleue et même texte blanc
que la variante pleine, dont elle ne diffère que par l'absence du glyphe. La
règle CSS a donc **disparu**, ainsi que son état de survol inversé.

**Écart assumé avec la maquette, sur demande du client.** Le PDF dessine ces
boutons contournés, et deux relevés le confirment sur `HP_06_Frame 54.pdf`
(bordures des trois boutons du pied de page à y=3657, 3816 et 3975) :

| | relevé sur la maquette |
| --- | --- |
| remplissage | (243, 248, 254) — le panneau, pas du blanc |
| texte | `#143776` — du bleu |

Du texte blanc impose un aplat foncé : c'est le contraire de contourné. La clé
`outline` garde son nom — c'est le mot de la maquette et la valeur enregistrée
côté ACF — mais ce qu'elle dessine a changé.

> Première lecture, corrigée : « les CTA n'ont pas de blanc dans la gélule »
> parlait du TEXTE, pas du fond. J'avais mesuré le remplissage, répondu juste à
> la mauvaise question, et retiré l'aplat blanc. La mesure était bonne, la
> question non.

### Le bus était écrasé, et le repère d'adresse trop petit

« Icônes écrasées » ne se voyait pas dans les boîtes : elles mesurent bien
24 × 24, mesuré. C'est le **tracé** qui était faux.

| glyphe | avant | maquette (relevé) | après |
| --- | --- | --- | --- |
| bus, hauteur de caisse | 8,5 / 24 | ~15 / 22 | 14,5 / 24 |
| bus, roues | détachées de 1,5 sous la caisse | chevauchant son bas | chevauchant de 0,75 |
| repère d'adresse, rayon | 4,25 | anneau touchant presque les bords | 8,75 |

Les assertions portent sur le **tracé** et non sur la boîte, faute de quoi elles
n'auraient rien vu : la caisse doit occuper plus de la moitié de la boîte, les
roues doivent chevaucher son bas, et tout glyphe annulaire doit avoir un rayon
d'au moins 8.

> Deux écarts relevés et NON corrigés, faute de demande : les aiguilles de
> l'horloge marquent 12 et 4h30 là où la maquette montre 12 et 3, et le glyphe
> « information » est un `i` là où la maquette dessine ce qui ressemble à un
> `1` cerclé. À arbitrer avec le designer.

## La révélation du pied de page

Le panneau bleu masque un visuel pleine largeur, puis se soulève en fin de page
et le découvre. Le principe vient d'une référence client (`piaget.com`) ; sa
règle CSS n'étant pas dans les feuilles servies, ce qui est ici suit la
description et la maquette.

### Le visuel NE BOUGE PAS

C'est tout l'effet, et c'est ce qui le distingue d'un simple dévoilement : le
visuel est **fixé au bas de la fenêtre** et peint **derrière** la page
(`z-index: -1`). Ce qui glisse par-dessus, c'est la page. Le panneau est un
volet qui remonte, alors que le visuel reste rigoureusement immobile.

**Aucun JavaScript** : l'effet est une pure géométrie de peinture. Il tient à
trois conditions, et retirer l'une des trois le supprime :

| condition | rôle |
| --- | --- |
| le visuel est `position: fixed; z-index: -1` | il ne suit plus la page, et passe derrière elle |
| la réserve du bloc ne peint **aucun fond** | c'est le trou par lequel on le voit |
| `.main-content` porte un aplat opaque | c'est ce qui le masque partout ailleurs |

L'aplat de `main` n'est pas un détail : `.block-info` n'a pas de fond propre, et
sans lui le visuel affleurerait sous cette section. Le fond du `body` ne
conviendrait pas — il est **propagé au canevas**, donc peint SOUS les `z-index`
négatifs. Il faut un fond de bloc en flux, que l'ordre de peinture place
au-dessus.

Mesuré sur le site réel, deux captures à 1440 × 900 — collé en bas, puis 200px
avant :

```
bord haut du visuel : 387px  puis  587px   (le volet a bougé de 200)
hauteur découverte  : 513px  puis  313px
pixels du visuel    : IDENTIQUES à toutes les lignes échantillonnées
```

Le volet bouge de 200px, le visuel de zéro.

### Deux versions précédentes, et ce qu'elles ont coûté

**Translater le panneau vers le bas** laissait une bande vide de **513px entre
la dernière section et lui**, visible pendant presque tout le défilement. Aucun
agencement où le panneau se déplace n'évite ce vide : sous la dernière section il
faut bien peindre quelque chose.

**Rétracter son BAS PEINT** au fil d'un avancement calculé en JavaScript réglait
ce vide — mais le visuel défilait alors avec la page. Ce n'était pas un volet :
tout bougeait ensemble, et seule la frontière se déplaçait. Le passage au visuel
fixé a supprimé 68 lignes de script, une variable CSS, une classe d'état et un
écouteur de défilement.

### Ce qui subsiste des deux versions

- **L'espace du visuel est RÉSERVÉ, pas emprunté** : c'est un `padding-bottom`
  sur le bloc, pas une marge négative sur le panneau. Une marge supprimait
  l'espace, et il n'y avait plus rien à découvrir.
- **La hauteur découverte se lit sur la réserve du bloc**, pas sur la variable
  CSS — une propriété personnalisée n'est pas résolue en pixels, `32.0625rem`
  donnait 32 après `parseFloat` — ni sur le visuel, qui est volontairement plus
  haut : il **remonte d'un rayon sous le panneau**, sans quoi les encoches des
  coins arrondis laissent voir ce qu'il y a derrière.
- **Le cadrage est du contenu**, choisi par le contributeur, et compensé pour
  être centré sur la partie VUE et non sur la boîte — voir `LcdsFocalPoint`.

### Sous mouvement réduit, le visuel redevient mobile

Un fond qui ne suit pas le contenu est un effet de **parallaxe**, et c'est
précisément ce que `prefers-reduced-motion` demande d'éviter : la gêne
vestibulaire vient du mouvement différentiel, pas du mouvement absolu. Le visuel
y redevient `absolute` et défile avec la page. Il reste visible, rien ne devient
inaccessible.

La campagne forçant cette préférence, le mode fixé y est éprouvé en **posant la
déclaration à la main** : deux défilements de 200px, et la boîte du visuel ne
doit pas bouger d'un pixel.

## Compilation

Webpack, via le conteneur jetable `node` (profil `tools`) :

```bash
docker compose run --rm node npm run build   # ou : dnpm run build
docker compose run --rm node npm run dev     # surveillance
```

`dist/` n'est pas versionné. La CI vérifie que le build passe, le déploiement le
rejoue — voir [`ci-cd.md`](ci-cd.md).

## Vérification

Le front a sa propre campagne d'assertions : voir [`qa.md`](qa.md).
