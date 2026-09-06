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

### La dernière entrée ne s'écarte pas

Elle borde le bouton « Prendre RDV », qui **n'appartient pas au menu** : il vit
sur son propre emplacement, flotte à côté et ne bouge jamais. Écarter la
dernière entrée la ferait pousser contre un voisin immobile.

Elle reste **poussée** par ses voisines — c'est leur écartement qui la déplace —
mais elle n'écarte personne, et n'ouvre donc aucun collet. Mesuré : au survol
de « Contact » la barre ne bouge pas d'un pixel (482 → 482) ; au survol de
« Les traitements » elle s'élargit bien de 40 (482 → 522) et pousse « Contact ».

### Deux écarts assumés avec la référence

- **`:focus-visible` en plus du survol.** floema.com ne prévoit que `:hover` —
  vérifié, zéro règle de focus sur ces boutons — donc un utilisateur au clavier
  n'y voit jamais l'animation.
- **Suspendue sous `prefers-reduced-motion`.** L'écartement déplace les voisins :
  c'est du mouvement. Le survol est alors signalé par un soulignement.

## La révélation du pied de page

Le panneau masque un visuel pleine largeur, puis se soulève en fin de page et le
découvre. Le principe vient d'une référence client (`piaget.com`) ; sa règle CSS
n'étant pas dans les feuilles servies, ce qui est ici suit la description et la
maquette.

Comme le parcours de soin, le script ne calcule **qu'un seul nombre** —
l'avancement — et le donne au CSS, qui possède chaque pixel. Et comme lui, il est
**opt-in** : la classe `footer-reveal--animated` n'est posée que par le script.
Sans JavaScript, ou sous `prefers-reduced-motion`, le panneau reste posé et le
visuel est simplement visible en dessous. C'est le rendu de la maquette, et rien
ne devient inatteignable.

### Ce qui bouge est le BAS PEINT, pas le panneau

Le panneau commence **immédiatement après le contenu**, comme la maquette le
dessine, et il n'en bouge jamais. Son fond et ses coins vivent sur un
pseudo-élément dont le bas déborde de 513px au repos — il masque alors le visuel
entièrement — puis se rétracte.

La première version translatait le panneau vers le bas. Elle laissait une bande
vide de **513px entre la dernière section et lui**, visible pendant presque tout
le défilement puisque l'avancement ne décolle que dans les 513 derniers pixels.
Le code l'assumait — « la bande laissée libre se confond avec le fond de la
page » — mais à l'écran ça se lit comme un trou, pas comme un panneau qui
remonte.

**Aucun agencement où le panneau se déplace n'évite ce vide.** Sous la dernière
section il faut bien peindre quelque chose : soit le panneau, soit le visuel,
soit rien. Descendre le panneau laisse le vide au-dessus ; le monter le laisse
en dessous ; ne rien réserver ne laisse rien à découvrir. Il fallait donc que ce
soit le **dessin** du panneau qui change, pas sa position.

La hauteur du panneau n'entre plus dans le mécanisme. L'invariant « panneau au
moins aussi haut que le visuel » a disparu avec elle : c'est le débord qui
masque, et il vaut exactement la réserve.

Trois pièges rencontrés, tous mesurés :

- **Une marge négative sur le panneau supprimait l'espace réservé au visuel** :
  il n'y avait alors plus rien à découvrir, et l'avancement restait à 0 en bas de
  page. C'est un `padding-bottom` sur le bloc, pas une marge sur le panneau.
- **La hauteur découverte se lit sur la réserve du bloc**, ni sur la variable
  CSS — une propriété personnalisée n'est pas résolue en pixels, `32.0625rem`
  donnait 32 après `parseFloat` — ni sur le visuel, qui est volontairement plus
  haut : il **remonte d'un rayon sous le panneau**, sans quoi les encoches des
  coins arrondis laissent voir le fond du bloc.

Le débord d'un rayon tient à **tout avancement** : le bas peint descend au plus
de la réserve, et le visuel monte de la réserve plus un rayon.

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
