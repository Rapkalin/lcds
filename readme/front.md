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

### Les jetons qui ne viennent PAS de Figma

Trois familles, sous `/* MOUVEMENT */`, parce que la bibliothèque Figma
n'expose ni durée ni courbe — les interactions de prototype ne sont pas lisibles
par le connecteur, voir [`design/figma/README.md`](../design/figma/README.md).

| Jeton | Ce qu'il vaut | Pourquoi il existe |
| --- | --- | --- |
| `$fade-duration` / `$fade` | `0.2s` / `0.2s ease` | Le fondu de TOUT changement de teinte du site. La valeur vivait en douze exemplaires dans cinq feuilles. |
| `$spread-out` / `$spread-in` | deux `cubic-bezier` relevées sur floema.com | L'écartement des pastilles, dans les deux sens. Partagé par le menu et les boutons primaires. |
| `$spread` / `$notch` | `20px` / `2px` | L'écart ouvert par le survol, et l'encoche au repos. |

**`$fade` est scindé en deux, et c'est le piège à connaître** : la durée sert
aussi de **délai**, sans son accélération. La `visibility` du panneau mobile est
retardée du temps que dure le fondu — sans quoi elle masquerait le panneau
pendant la première moitié de son apparition. Une durée changée d'un côté et pas
de l'autre démasquerait le panneau avant la fin du fondu, sans que rien ne le
signale.

> `$blue-hover` est le seul jeton de couleur dérivé plutôt que relevé :
> `color.adjust($blue, $lightness: -6%)`. Il sert aux aplats **et** au tracé de
> la silhouette, qui doivent foncer de la même quantité — sinon l'écart de teinte
> se voit en travers du bouton.

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
  500ms — elle couvre la transition CSS de 450ms), donc plusieurs crans
  rapprochés valent un seul.

C'est **un chrono qui règle le rythme, plus une distance**. Chaque carte est donc
tenue le même temps, la dernière comprise — ce que les réglages de hauteur ne
parvenaient pas à obtenir.

#### Trois constantes, et pourquoi il en faut trois

Une cadence fixe ne suffit pas, et c'est le premier défaut qu'elle a produit :
**deux étapes passaient d'un seul geste.**

Un geste de pavé tactile — et une roulette sous macOS — n'émet pas un évènement
mais une **traîne**, qui continue près d'une seconde après que le doigt a quitté
la surface. À l'expiration de la cadence, l'inertie encore vivante déclenchait une
seconde bascule. D'où :

| | rôle |
| --- | --- |
| `JOURNEY_CADENCE` (500ms) | plancher après une bascule ; couvre la transition CSS |
| `JOURNEY_REPOS` (150ms) | **silence** au-delà duquel un évènement ouvre un geste NEUF |
| `JOURNEY_PLAFOND` (1200ms) | sortie de secours, comptée depuis la dernière bascule |

Un geste neuf se reconnaît à un **silence qui le précède**. Tant que les
évènements se suivent, c'est le même geste qui vit sur son inertie, et il a déjà
eu son étape — quelle que soit la durée de sa traîne. C'est ce qui rend le
blocage **catégorique** et non seulement probable : une cadence fixe, elle,
finissait par expirer sous une traîne d'une seconde et laissait passer une
seconde carte.

#### Les deux valeurs ont été abaissées, et elles ont chacune un plancher DUR

Retour client : « quand j'arrive sur une étape, si je scroll plusieurs fois
c'est bloqué […] c'est absorbé pendant trop longtemps ». La cadence est passée
de 600 à **500ms**, le plafond de 1600 à **1200ms**.

Lequel agit dépend du périphérique, et c'est ce qui rend le réglage
contre-intuitif : à la molette, avec des crans espacés de plus de
`JOURNEY_REPOS`, c'est la **cadence**. Au pavé tactile, où le flux ne s'arrête
jamais et où aucun geste neuf ne peut donc être reconnu, c'est le **plafond
seul** — donc 1,6 seconde d'attente avant l'ajustement.

Ni l'un ni l'autre ne peut descendre plus bas sans casser quelque chose :

| Constante | Plancher | Ce qui casse en dessous |
| --- | --- | --- |
| `JOURNEY_CADENCE` | **450ms** | La transition CSS du rail. L'étape suivante partirait avant que la précédente soit posée. |
| `JOURNEY_PLAFOND` | **1000ms** | La traîne d'un pavé tactile. Elle franchirait le plafond seule, et un geste passerait deux cartes. |

> **Le second plancher est mesuré, pas supposé.** Plafond ramené à 900 : deux
> assertions rougissent en annonçant `650px`, soit exactement une étape de trop
> — « une traîne d'une seconde ne vaut qu'une étape » et « l'arrivée n'emporte
> pas la première carte ». Il reste 200ms de marge à 1200.

Le seul levier restant serait de **raccourcir la transition CSS**, ce qui
abaisserait mécaniquement le plancher de la cadence. Ça change le glissement
lui-même, donc le ressenti du bloc : écarté pour cette raison.

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

> Ce mécanisme sert désormais **aussi les boutons d'action primaires**, par le
> même code — `initPillShape`. Voir « Les boutons primaires ont pris la
> silhouette du menu » plus bas : ce qui suit décrit le cas de la navigation,
> qui vide ses pastilles ; le bouton d'action, lui, garde leur aplat, et la
> raison vaut d'être lue.

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
`outline` garde son nom — c'est le mot de la maquette — mais ce qu'elle dessine a
changé.

> **Renversé par le retour suivant**, et la mesure de `#143776` corrigée : voir
> « Le secondaire repasse sur fond transparent » plus bas. Ce qui reste vrai ici,
> c'est que la variante n'a pas de rendu propre à inventer — elle suit la
> maquette. Et que `outline` n'est la valeur d'AUCUN champ ACF : elle est écrite
> en dur par ses deux appelants.

> Première lecture, corrigée : « les CTA n'ont pas de blanc dans la gélule »
> parlait du TEXTE, pas du fond. J'avais mesuré le remplissage, répondu juste à
> la mauvaise question, et retiré l'aplat blanc. La mesure était bonne, la
> question non.

### Et pas de règle `:visited`

Le texte est resté bleu **une fois le lien visité**, alors que deux mesures
disaient « blanc ». La cause : `a:visited { color: inherit }` dans
`basics/general.scss`.

Sa spécificité (0,1,1) l'emporte sur toute classe de composant (0,1,0) : le
`color: var.$white` de `.cta` perdait, et la pastille héritait du bleu de son
conteneur. La règle a été **supprimée**, pas contournée par une exception sur
`.cta` — sinon le prochain composant à colorer un lien retomberait dedans.

Elle était inutile. Une déclaration d'auteur l'emporte sur celle du navigateur
**par l'origine**, avant toute question de spécificité, et la preuve est sous
les yeux : les liens NON visités du site n'affichent pas le bleu du navigateur,
ils héritent déjà par le `a { color: inherit }` qui reste.

> **Ce défaut était invisible à la recette automatisée, et le restera.**
> `getComputedStyle` ment délibérément sur `:visited` — c'est une protection de
> la vie privée — et un profil de navigateur neuf n'a de toute façon aucun
> historique. J'ai tenté de fabriquer cet historique avec un profil persistant :
> les deux captures rendent du blanc dans les deux cas, visité ou non.
>
> L'assertion porte donc sur la RÈGLE : aucun sélecteur `:visited` ne doit
> subsister dans la feuille servie. C'est le seul angle mesurable.

### Les glyphes des informations pratiques viennent du client

Les cinq tracés ont été **fournis par le client** et sont repris tels quels, un
par composant — `components/icon-<valeur>.php`, que `LcdsInfoIcon::template()`
résout sans qu'aucun nom de fichier soit écrit dans un gabarit.

Trois adaptations à l'export, et elles valent d'être connues :

- **`stroke="#048B8C"` répété sur chaque tracé devient un `currentColor` hissé
  sur le `<g>`.** La teinte vient alors de la feuille de style, donc du jeton,
  et le glyphe se recolore où qu'il serve. Un turquoise en dur aurait figé une
  valeur que la bibliothèque Figma est seule à porter.
- **La boîte rendue est ramenée à 24**, la colonne d'icône que la maquette
  mesure. Les exports font 26 : rendus tels quels, ils débordent de 2px. Le
  `viewBox` garde ses 26, c'est lui qui porte le dessin.
- **Le bus est livré sur 26 × 22.** Il est recadré par `viewBox="0 -2 26 26"` —
  origine décalée de 2 vers le haut — plutôt qu'étiré à 24 × 24. Les cinq
  glyphes partagent ainsi la même boîte, sans distorsion.

Les tracés eux-mêmes ne sont pas éprouvés : ce sont les dessins du client, pas
des cotes de maquette à retrouver. Ce qui l'est, c'est leur **intégration** —
aucun débord de colonne, un trait en `currentColor`, une teinte prise sur le
jeton. C'est exactement ce qu'un copier-coller d'export casse en silence.

### Ce qui précédait ces tracés

Avant qu'ils arrivent, deux glyphes redessinés d'après le PDF avaient déjà été
corrigés — le bus écrasé et le repère d'adresse trop petit. Le relevé garde sa
valeur de méthode.

#### Le bus était écrasé, et le repère d'adresse trop petit

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

## Le lot de retours suivant

Deux retours du client, traités ensemble. Le premier **revient en partie** sur la
livraison précédente : les boutons secondaires redeviennent contournés.

### Le secondaire repasse sur fond transparent

« Les CTA du pied de page doivent être sur fond transparent et non sur fond
bleu. » C'est le dessin de la maquette, contre la demande précédente de texte
blanc partout — et les deux ne peuvent pas tenir ensemble : du blanc sur le
panneau `#F2F8FF` mesure **1,06:1**. Fond transparent impose donc un texte bleu.

Relevé au pixel sur `HP_06_Frame 54.pdf`, rendu à 216 dpi puis échantillonné :

| | mesuré | jeton |
| --- | --- | --- |
| remplissage | `#F2F8FF` — le fond lui-même | transparent |
| bordure | `#A8BED6`, 1px (3 px device à 216 dpi) | `$blue-veil` |
| texte | `#00387A` | `$blue` |
| hauteur | 29 | inchangée |

> L'entrée 2.13.0 du journal donnait ce texte à `#143776`. C'est faux : la mesure
> donne `$blue` exactement. L'erreur venait d'un pixel d'antialiasing.

#### Ce que `$blue-light` était vraiment

Le CSS Figma de l'état de survol donne `rgba(0, 56, 122, 0.3)` en remplissage.
Ce voile posé sur le panneau `$blue-pale` compose **#A9BED7** — soit `#A8BED6` à
une unité près sur deux canaux, l'erreur d'échantillonnage du PDF.

Autrement dit : **`$blue-light` n'est pas un jeton manquant de la bibliothèque
Figma, c'est `$blue` à 30 % aplati sur un fond connu.** La note qui demandait de
le faire promouvoir côté design tombe. La feuille déclare désormais le voile,
`$blue-veil`, et l'utilise pour la bordure au repos comme pour le remplissage au
survol : c'est la même valeur, à deux endroits du même composant.

Le voile suit son fond, la teinte aplatie non. Ça compte ici : le « voir le
plan » des informations pratiques ne vit pas sur le panneau, et un `#A8BED6` en
dur y aurait trahi le dessin.

#### Le piège du double voile

La bordure **s'efface** au survol au lieu de rester sous le remplissage. Deux
voiles à 30 % superposés ne composent pas 30 % : l'alpha effectif monte à 0,51 et
donne `#7696BB`, un anneau foncé de **51 unités d'écart** avec l'intérieur, là où
la maquette montre un aplat uni. Le fond continue de peindre sous la bordure
devenue transparente et remplit toute la pastille.

Contrastes mesurés du texte : **10,64:1** au repos, **5,97:1** au survol.

#### La portée

Une seule règle, sur `.cta--outline` : les quatre boutons du pied de page **et**
le « voir le plan ». La maquette les dessine tous les deux contournés — le relevé
`243:352` mesure ce dernier à 131 × 30. Une troisième variante aurait imposé deux
styles de bouton contourné à tenir en parallèle.

### Les boutons primaires ont pris la silhouette du menu

« Les CTA primaires avec une petite icône à gauche doivent se comporter comme le
menu au hover. » Le bouton primaire a exactement la structure d'une rangée du
menu : deux pastilles à relier. Le code du menu a donc été **généralisé** plutôt
que recopié.

`initNavShape` s'est scindé en deux :

- **`initPillShape`** prend la racine, le sélecteur du SVG, celui des pastilles,
  la classe à poser et un prédicat d'activité facultatif. Tout le reste — le
  relevé des boîtes, la garde « une seule rangée », la boucle par échéance — est
  commun. Il renvoie sa fonction de mise à jour, que l'appelant rebranche sur ses
  propres signaux.
- **`initNavShape`** et **`initCtaShapes`** ne sont plus que deux appels.

`cheminBarre` devient `cheminSilhouette`, et `NAV_*` devient `PILL_*` : les
constantes ne sont plus celles de la navigation.

Deux détails qui font que ça marche sans code supplémentaire :

- **L'écart est une MARGE, pas le `gap` du conteneur.** `initPillShape` écoute
  `transitionstart` filtré sur `margin`, exactement comme pour le menu. Un `gap`
  animé aurait émis `column-gap` et demandé un second filtre.
- **L'écouteur est posé sur la racine et non sur la liste.** Les évènements de
  transition remontent : un seul écouteur couvre les pastilles à n'importe quelle
  profondeur, ce qui rend le paramètre inutile.

Conséquence assumée sur le repos : l'encoche nette de 2px devient une **taille de
guêpe**, comme entre deux entrées du menu. C'est le prix de la silhouette
continue, et c'est ce qui a été arbitré.

#### La régression que la campagne a attrapée

Première version : les pastilles étaient **vidées** au profit du seul `<path>`,
comme le fait la barre de navigation. La campagne est passée au rouge sur une
assertion qui ne visait pas ce composant :

```
FAIL :: contraste du texte (6 sous le seuil : span 1.07:1 < 4.5, …)
```

Six libellés, un par pastille des trois boutons primaires. Le rendu était juste à
l'œil — blanc sur la silhouette bleue, 11,7:1 — mais **aucun contrôleur de
contraste ne voit un SVG**, et en mode couleurs forcées, où le tracé peut ne rien
peindre, c'était du blanc sur blanc pour de bon.

La barre de navigation ne souffre pas du même défaut parce que son texte est
bleu : vider ses pastilles la laisse lisible.

Le correctif tient en une observation de géométrie : **le tracé passe en dehors
de l'arrondi de chaque pastille au droit du collet**, qu'il enjambe. Chaque
pastille tient donc tout entière dans la silhouette. Lui rendre son aplat bleu ne
change rien au rendu — deux aplats de la même teinte, le second inclus dans le
premier, aucune couture possible — et rend le contraste mesurable. La silhouette
n'ajoute plus que le collet.

> Vérifié dans les deux sens : la campagne était **verte sur `HEAD`** avant ce
> lot, les deux seuls échecs restants portant sur la couleur de puce et
> préexistant. L'assertion de contraste a donc bien détecté quelque chose de
> neuf.

Le survol fonce l'ensemble — pastilles **et** collet, de la même quantité, par le
jeton `$blue-hover`. La barre de navigation, elle, ne repeint rien : l'écartement
lui suffit. Ici le repeint est gardé parce qu'il est le seul signal dans deux cas
où l'écartement n'a pas lieu : sans JavaScript, et sous `prefers-reduced-motion`.
Foncer les pastilles seules aurait laissé le collet en clair en travers du bouton.

### Ce que l'audit du lot a corrigé

Trois points relevés en relisant la livraison, avant de la donner pour finie.

- **`variant` était cinq chaînes magiques** — `'solid'` / `'outline'` répétées
  dans `cta.php` et ses deux appelants — pour un domaine à deux valeurs, alors
  que le thème porte huit enums pour exactement ça. D'où
  `inc/enums/LcdsCtaVariant.php` : le **nom du cas** est le mot de la maquette
  (`Primary`, `Secondary`), sa **valeur** est la classe CSS (`solid`,
  `outline`). Les deux vocabulaires divergeaient, la correspondance vit
  désormais en un seul endroit. `hasIcon()` remplace le test `=== 'solid'` du
  gabarit : c'est la seule différence de balisage entre les deux variantes.
  Volontairement **sans `choices()` ni `label()`**, contrairement aux autres
  enums : aucun champ ACF n'offre la variante, ce serait du code mort.
- **`0.2s ease` en douze exemplaires**, et j'en avais ajouté trois. Devenu
  `$fade` — voir « Les jetons qui ne viennent pas de Figma ». La factorisation a
  été vérifiée par comparaison du **CSS compilé avant/après : identique**.
- **La boucle de `initCtaShapes`** utilisait `for…of Array.from(…)` là où le
  reste du fichier itère une NodeList avec `.forEach`.

### La galerie de L'HISTOIRE est pilotée par le défilement de la page

Troisième retour du lot : « le scroll se bloque sur le carrousel, le scroll
horizontal doit être fluide, il reprend une fois que le scroll a atteint la
dernière image ». La bande reste collée le temps qu'une réserve s'épuise, et
l'avancement dans cette réserve **donne** la position du rail.

#### Relevé sur la référence du client

`buildcover.com`, le bloc `module module-mediaGroup` et son enfant
`module-mediaGroup-scroller`. C'est **GSAP ScrollTrigger avec `pin`** : la
bibliothèque insère un `pin-spacer` dont les styles en ligne disent tout.

| Mesure | Relevé |
| --- | --- |
| Réserve de défilement | `padding-bottom: 1212px` sur le `pin-spacer` |
| Course horizontale | `scrollWidth 3748 − clientWidth 2536` = **1212px** |
| Élément épinglé | `position: fixed`, calé à `top: 256px` |
| Porteur du déplacement | **`scrollLeft`** de `.module-mediaGroup-inner`, en `overflow-x: hidden` |
| Transformations | `translateX` reste à 0 sur le scroller comme sur l'inner |
| Composition | 4 visuels de 738px, `gap: 20px`, 368px de rembourrage de chaque côté |

**Le nombre décisif : réserve = course = 1212px, exactement.** C'est
l'association absolue à l'échelle 1:1 — un pixel de défilement de page vaut un
pixel de rail. Et c'est la RÉSERVE qui porte ce rapport : il n'y a aucun facteur
à régler dans le script, ce qui est la raison de ne pas en introduire un.

> **Ce qui reste supposé.** La présence d'un amortissement entre le défilement et
> `scrollLeft` (`scrub: true` contre `scrub: <nombre>` chez GSAP) n'a pas pu être
> mesurée : les sondes en `requestAnimationFrame` ont fait expirer le lien CDP à
> trois reprises, et le défilement programmatique ne pilotait pas le déclencheur.
> L'égalité réserve = course plaide pour l'absence de retard, mais un lissage ne
> changerait pas la réserve.

#### Ce qui a été retenu, et ce qui s'en écarte

- **`position: sticky` et non `fixed`.** La référence utilise GSAP et doit alors
  calculer elle-même le calage. Le collage natif n'a pas ce coût, et c'est déjà
  ce que fait la vue épinglée du parcours de soin.
- **La bande fait un écran** (`100svh`), le carrousel centré dedans. Sans quoi la
  page se bloquerait alors qu'un morceau de la section suivante est déjà visible.
  La référence cale sa bande à 256px du haut, soit 20 % de sa hauteur de vue :
  c'est son cadrage, pas le nôtre, et **aucune maquette LCDS ne se prononce**.
- **`overflow-x: hidden` sur le rail épinglé**, comme la référence. C'est ce
  détail qui résout le problème des deux écrivains : le défilement horizontal
  natif est coupé, donc le script est SEUL à écrire `scrollLeft`. Deux écrivains
  se disputeraient la position à chaque image.

#### Pourquoi ce n'est pas la mécanique du parcours de soin

Le parcours **confisque la molette** et porte la page d'une étape à l'autre : un
cran, une étape, avec un chrono qui absorbe le reste. C'est du magnétisme, et
c'est ce que le client ne voulait pas ici.

Ici rien n'est confisqué : la page défile normalement, et le rail est une
*fonction* de son avancement. D'où deux propriétés que la confiscation
n'aurait pas données — le **tactile** et le **clavier** marchent sans traitement
particulier, puisqu'ils font défiler la page comme le reste.

#### Ce que l'épinglage retire, et ce qui le remplace

Trois comportements du rail natif tombent quand l'épinglage est actif, et chacun
a sa compensation :

| Ce qui tombe | Pourquoi | Ce qui reste |
| --- | --- | --- |
| Le défilement horizontal au geste | `overflow-x: hidden` | La page défile, le rail suit |
| Le glisser-déposer à la souris | Il écrirait une position aussitôt écrasée | Les flèches — l'alternative exigée par le WCAG 2.5.7 |
| L'arrêt de tabulation du rail | Il ne défile plus rien : ce serait un piège au clavier | Les flèches sont de vrais boutons focalisables |

Les flèches, elles, **pilotent le défilement de la page**. Le rapport étant de
1:1, une page de rail vaut exactement une largeur de rail en défilement vertical.
L'indicateur et l'état désactivé des boutons continuent de lire `rail.scrollLeft`
et n'ont rien demandé.

Le composant est partagé avec la section Technologies : l'épinglage est donc
**déclaré** par la section qui compose le carrousel — `'pinned' => true` dans
`layouts/histoire.php` — et jamais déduit d'un sélecteur parent. Ce n'est pas un
champ ACF : c'est une décision de conception, pas un choix de contribution.

#### Trois sorties de secours

Comme la vue épinglée du parcours, l'épinglage **n'est pas posé** sous le point
de rupture (la bande mangerait toute la vue d'un téléphone, où le rail se balaie
déjà au doigt), sous `prefers-reduced-motion` (RGAA 13.8, voir
[`accessibilite.md`](accessibilite.md)) et sur un rail qui tient dans la vue —
où il n'y aurait rien à parcourir. Dans ces trois cas le carrousel est celui de
partout ailleurs.

> **Conséquence sur la recette** : la campagne force `prefers-reduced-motion`,
> donc l'épinglage n'y est jamais posé de lui-même. Les assertions du rail natif
> n'ont eu à changer — c'est le repli qu'elles mesurent. L'état épinglé est
> forcé par la campagne pour éprouver l'association. Voir [`qa.md`](qa.md).

### Le hero se fait recouvrir comme par un volet

Quatrième retour du lot : « le premier bloc en-dessous de la home remonte et
passe par dessus au scroll pour cacher petit à petit la hero banner ». Le hero
**ne bouge pas** — il reste collé en haut de la vue — et c'est la section
suivante qui remonte et le recouvre.

**Aucun JavaScript.** `position: sticky` sur le hero, `position: relative` et
`z-index: 1` sur ses frères. La géométrie de peinture suffit, comme pour la
révélation du pied de page.

Mesuré sur le site, hero de 900 :

| Défilement | Haut du hero | Haut de la section suivante |
| --- | --- | --- |
| 0 | 0 | 900 |
| 300 | **0** | 600 |
| 600 | **0** | 300 |
| 900 | **0** | 0 — hero entièrement couvert |

#### La règle porte sur les FRÈRES, jamais sur une section nommée

C'est la contrainte que le client a posée avec le retour : les sections sont un
contenu flexible, **le contributeur les réordonne**, et n'importe laquelle peut
se retrouver sous le hero. Un sélecteur `.hero ~ *` couvre celle qui suit quelle
qu'elle soit — et la nouvelle si l'ordre change demain. Une règle écrite sur
`.block-intro` aurait été fausse au premier glisser-déposer dans l'admin.

#### Le piège : un fond de parent ne recouvre rien

Ce qui rend le volet opaque n'est pas `.main-content`, qui porte pourtant un
aplat blanc : **le fond d'un parent peint SOUS ses enfants**. Il ne peut donc
pas masquer un frère collé derrière eux. C'est le fond **propre** de chaque
section qui fait le volet.

`.block-info` n'en avait pas — elle se contentait du blanc hérité. Placée sous
le hero, elle l'aurait laissé transparaître. Elle porte désormais le sien, ce
qui ne change rien à son rendu actuel.

> Le pied de page avait déjà rencontré ce cas exact, et son commentaire le dit :
> « le visuel affleurerait sous toute section dépourvue de fond propre —
> `.block-info` n'en a pas ». Le même piège, deux effets différents.

**Une assertion de recette verrouille l'invariant** pour les sections à venir :
aucun frère du hero ne doit avoir de fond transparent. Éprouvée par mutation —
le fond de `.block-info` retiré, elle rougit **en nommant la section fautive**.
C'est le seul contrôle des trois qui protège la contribution ; les deux autres
portent sur la règle, la campagne forçant `prefers-reduced-motion`.

#### Désactivé sous mouvement réduit

Un fond qui ne suit pas le contenu est un effet de parallaxe. La règle est donc
sous `prefers-reduced-motion: no-preference`, comme le parcours de soin et la
révélation du pied de page — RGAA 13.8, voir
[`accessibilite.md`](accessibilite.md). Le hero y défile normalement.

> **Réserve, non mesurée** : le hero reste collé pendant TOUTE la page, occulté
> derrière les sections. C'est visuellement correct, mais ça maintient une
> couche de composition pleine vue jusqu'au pied de page. Le coût sur un
> appareil modeste n'a pas été relevé.

### Les sections empilées arrondissent leurs coins hauts

Cinquième retour du lot : un rayon de **48px** en haut de chaque section.

**ÉCART ASSUMÉ AVEC LA MAQUETTE.** Le PDF dessine des jonctions franches, relevé
au pixel sur `HP_01_LCDS_hp full.pdf` : à x=4, l'image du hero court jusqu'à
y=899 et le bleu pâle commence exactement à **y=900**. Avec un rayon de 48, il
ne commencerait qu'à y≈929 à cette abscisse. C'est une demande postérieure, pas
une correction.

#### Le rayon seul ne suffit pas

Une section aux coins arrondis laisse voir, dans l'encoche des deux coins, ce
qui peint **derrière** elle. Et derrière, c'est le blanc de `.main-content` :
deux oreilles claires à chaque épaule. Le fond d'une section ne s'étend pas sous
sa voisine.

Chaque section **chevauche donc la précédente** de la valeur du rayon, par une
marge haute négative. L'encoche montre alors la section d'avant, et l'épaule est
propre. Les deux déclarations vont ensemble : la campagne les éprouve ensemble.

#### Sauf sous le hero, où le bloc arrive après

**Arbitré par le client** : le bloc qui suit le hero ne le chevauche pas. La
maquette montre un recouvrement à cet endroit pour dire l'intention de volet,
pas pour être reproduit. Le volet reste entier de toute façon — c'est le hero
collé qui le produit au défilement, pas ce décalage de 48px.

Là, l'encoche n'a pas besoin du chevauchement : c'est **le hero** qu'elle laisse
voir, puisqu'il est collé derrière. Vérifié par sondage du point exact de
l'encoche, sur toute sa hauteur, à sept positions de défilement — seuls `hero`
et la section elle-même y apparaissent.

> **Une exception mesurée, et non corrigée.** Sur une vue PLUS HAUTE que le hero
> — donc au-delà de 900px, où celui-ci est plafonné et ne remplit plus l'écran —
> le blanc de `.main-content` apparaît dans l'encoche pendant les **48 premiers
> pixels de défilement**, le temps que le hero collé vienne se placer derrière.
> Deux petites encoches claires aux épaules, transitoires.
>
> Reproduit en réduisant la hauteur du hero à 500 sur une vue de 797 : à
> défilement 0, l'encoche montre `main-content` ; à 60, elle ne montre plus que
> `hero`. Deux remèdes possibles si ça gêne — supprimer le rayon sur le seul
> bloc qui suit le hero, ce que la maquette dessine d'ailleurs ; ou étendre la
> surface peinte du hero de 48px sous lui, ce qui touche au cadrage de son
> visuel.

> C'est le même piège que le volet du hero, pris par l'autre bout — là, il
> fallait un fond PROPRE à chaque section ; ici, il faut que ce fond DÉBORDE
> sous sa voisine. Dans les deux cas, la cause est qu'un fond ne peint ni sous
> ses enfants ni sous ses frères.

#### Ce que la règle ne nomme pas

Elle porte sur la **position dans la liste** — `.front-page > * + *` — et jamais
sur une section nommée : les sections sont réordonnables. Deux exclusions :

- **le hero**, qui est une bannière pleine largeur et non un panneau ;
- **`.screen-reader-text`**, le `h1` de la page, enfant du même conteneur et hors
  flux. Un rayon sur lui ne se verrait pas, mais il n'est pas une section.

Le rayon vit dans `$radius-section`, distinct de `--footer-radius` qui vaut 64 et
arrondit les coins BAS du panneau de pied de page. Deux rôles, deux valeurs :
les confondre ferait bouger l'un en corrigeant l'autre.

### Un seul panneau ouvert à la fois, par GROUPE

Retour client : « pour les accordéons sur le site de manière globale, un seul
accordéon ne peut être ouvert à la fois ».

**Le groupe est déclaré, jamais deviné.** `data-disclosure-group` sur un
ancêtre — la liste de `components/accordion.php`, la section des technologies —
et le script referme les voisins de ce groupe-là. Trois conséquences voulues :

- replier une carte de technologie **ne touche pas** l'accordéon des
  traitements, alors que les deux coexistent sur la page d'accueil ;
- un panneau posé hors de tout groupe reste **indépendant**, plutôt que de
  fermer le reste de la page par surprise ;
- déplacer la règle d'un cran — la porter sur `.main-content` — suffirait à
  rendre l'exclusivité globale à la page, si le besoin change.

> **Lecture retenue de « de manière globale »** : la règle vaut partout, elle
> ne ferme pas tout partout. La différence est observable dès la page d'accueil,
> qui porte les deux groupes. À renverser si l'intention était l'autre.

**La règle s'applique aussi au CHARGEMENT.** Le champ « ouvert » est
contribuable : rien n'empêche d'en cocher deux dans l'administration, et la page
s'ouvrirait alors dans un état que le premier clic ne rattraperait pas.

Et **tout peut être refermé** : un second clic sur un panneau ouvert le replie.
L'exclusivité ne devait pas transformer l'accordéon en sélecteur à choix
obligatoire.

### L'accordéon est un composant

`components/accordion.php`, extrait de `block-treatments.php` où il vivait en
dur. Il prend `items` et un `heading` — un accordéon posé directement sous un
`h1` prend `h2`, sinon la hiérarchie saute un niveau sur la nouvelle page.

C'était le seul balisage de section encore écrit sur place. **Tout le reste
l'était déjà** : vérifié sur les seize fichiers de `components/`, aucun ne lit
ACF. Ce sont les `layouts/*.php` qui lisent les sous-champs et délèguent. Poser
une section sur une autre page ne demande donc rien de plus qu'un
`get_template_part` avec les bons arguments — voir
[`contribution.md`](contribution.md).

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
