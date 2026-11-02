# QA

Trois portes, à passer avant tout commit :

| Commande | Couvre |
| --- | --- |
| `dcheck` (`composer check`) | Style PER, types natifs, PHPStan niveau 6 |
| `dtest` (`composer test`) | Contrats des enums (cache, emplacements de menu) |
| `bin/qa-front.sh` | Compilation, assets servis, invalidation du cache, rendu et comportement de l'en-tête |

Les deux premières sont le garde-fou du hook de pré-commit et de la CI — voir
[`qualite-code.md`](qualite-code.md). La troisième est locale : elle a besoin
d'un navigateur et des conteneurs en marche.

## `bin/qa-front.sh`

```bash
docker compose up -d
bin/qa-front.sh              # recompile puis vérifie
bin/qa-front.sh --no-build   # sans recompiler
```

Prérequis : Google Chrome ou Chromium. Le script ne touche qu'à `dist/`, qui
n'est pas versionné, et nettoie derrière lui même en cas d'interruption.

### Ce qu'il vérifie

- **Compilation** : `npm run build` passe.
- **Assets servis** : la page d'accueil, `main.css` et `main.js` répondent 200.
- **Invalidation du cache** : la version de l'URL d'un asset **change** quand le
  fichier change. C'est un test de comportement, pas de valeur — voir
  [`front.md`](front.md).
- **En-tête, à 1440 et 500px** : logo, navigation et bouton d'action rendus ;
  pas de débordement horizontal ; burger masqué et liens en ligne en desktop ;
  en mobile, ouverture au clic, fermeture par Échap et au clic sur un lien,
  défilement bloqué, et bascule de `visibility` au bon moment.

- **En-tête collé, à chaque largeur** : `fixed` au-dessus d'un hero et `sticky`
  ailleurs ; page défilée jusqu'en bas, l'en-tête est **toujours à `top: 0`** ;
  `--header-height` publiée égale à la hauteur mesurée ; et l'en-tête **reste
  transparent**, lui comme son pseudo-élément — la décision de ne poser aucun
  fond au défilement est gardée par une assertion.
- **Carrousel, à 1440** : cotes du rail, comportement des deux boutons (dont
  deux clics rapprochés qui se cumulent), course égale à la somme des visuels,
  et le **glisser-déposer** — 100px de geste donnent 100px de défilement, le
  clic qui suit est avalé, un geste sous le seuil de 6px ne bouge rien et
  n'avale pas le clic, un geste **tactile** est laissé au défilement natif.
- **Parcours de soin, à 1440** : six étapes, une page par étape et calage
  obligatoire ; les cotes de la maquette « Desktop - 10 » (visuel, corps, barre,
  flèches) ; le segment vaut un sixième de la piste ; « précédent » grisé sur la
  première étape, « suivant » sur la dernière, une étape par clic ; et la
  section a retrouvé le délai de volet commun. Éprouvé en retirant le calage et
  la hauteur d'étape de la feuille compilée : quatre assertions rougissent.
- **Aucun défilement mort après un rail épinglé** : entre le bas de la réserve
  et le bas de la section, il ne reste que le chevauchement. Éprouvé par les deux
  mutations — l'exception retirée rend les 110px signalés en recette, la cale
  seule réintroduite en rend 30.
- **La borne du décalage de collage**, dans les deux sens : aucun décalage ne
  range une étiquette derrière le menu, ET des sections se figent encore. La
  seconde est indispensable — la première se satisfait aussi d'un volet
  entièrement désactivé, ce qu'une borne élargie d'un cran de trop produirait
  sans que rien d'autre ne bronche.

> Cette famille d'assertions a rougi sur du code juste avant de rougir sur un
> vrai défaut. L'ancienne version exigeait que l'étiquette des technologies reste
> **toujours** sous le menu : elle tenait tant que cette section restait bornée,
> et tombait dès qu'une vue courte la faisait légitimement se figer — mesuré à
> 700, étiquette à −201 alors que rien n'était caché. Elle vérifie désormais
> qu'on n'est pas dans la ZONE interdite, au lieu d'exiger une seule des deux
> issues acceptables.
- **Conversion WebP**, éprouvée sur les deux moitiés parce qu'elles peuvent
  diverger : côté **serveur**, le module chargé, chaque format source mappé, le
  GIF et le SVG laissés dehors, la qualité visant le WebP seul, et surtout un
  **encodage réel** dont les octets produits commencent par `RIFF`/`WEBP` ; côté
  **front**, chaque `src` et **chaque piste de `srcset`** d'un visuel de
  médiathèque servi en `.webp`, sur les deux pages visitées.

> Cette panne-là est **silencieuse** : sans support WebP dans la bibliothèque
> d'images, WordPress retombe sur du JPEG sans rien signaler. Le type MIME qu'il
> annonce ne suffit donc pas — c'est le fichier produit qu'il faut relire. Et
> des filtres corrects ne prouvent pas que les médias en base ont été
> régénérés : d'où les deux moitiés. Voir [`images.md`](images.md).

- **Cotes des blocs de fin, à 1440** : étiquettes, boutons, rail plein-bord,
  largeurs et inclinaisons des cartes, colonnes et filets des informations
  pratiques — comparés aux relevés du PDF.

> La branche desktop du pilote se terminait par un `return` anticipé : **tout ce
> qui suivait — cotes des blocs de fin et campagne d'accessibilité — ne tournait
> qu'aux largeurs mobiles.** Constaté en cherchant pourquoi les cotes des
> technologies n'apparaissaient pas à 1440. C'est désormais un `if/else`.

- **Pied de page** : cotes du panneau à 1440 ; les **trois conditions de
  peinture** de la révélation — visuel fixé, réserve sans fond, aplat sur
  `.main-content` — chacune éprouvée par sa suppression ; le visuel **ne bouge
  pas** sur 200px de défilement une fois fixé, et défile bien avec la page sous
  mouvement réduit ; cadrages et débord d'un rayon sous le panneau.

> La campagne force `prefers-reduced-motion`, donc le visuel y est `absolute`.
> Le mode fixé est éprouvé en posant la déclaration à la main — deux
> défilements de 200px, et la boîte ne doit pas bouger d'un pixel.
>
> L'effet lui-même a été mesuré **au pixel**, hors campagne : deux captures du
> site à 1440 × 900, collé en bas puis 200px avant. Le bord du volet passe de
> 387 à 587px pendant que les pixels du visuel restent identiques. C'est le
> genre de preuve qu'aucune assertion de DOM ne remplace.

- **Rayons, à 1440** : les contrôles de la page à **4**, les étiquettes à
  **10**, et l'étiquette comparée à sa propre demi-hauteur — `border-radius:
  999px` se clampe et le style calculé rend `999px` quand même, donc lire le
  jeton ne dirait rien du rendu. Les assertions nomment l'élément fautif et sa
  valeur.

> **Deux assertions ont été recalées sur le relevé, pas assouplies.** Celle du
> collet du menu bornait 50–75 % au nom d'un « 61 % relevé » : en remettant le
> rayon à 8, l'implémentation rend **61 %** — le chiffre venait du code qu'elle
> était censée éprouver. Mesuré colonne par colonne sur les quatre maquettes, le
> pincement vaut **79 %**. Et celle de la puce d'entrée courante mesurait 0
> d'élargissement sur « Le cabinet », parce que la PREMIÈRE entrée y est déjà
> l'entrée courante : elle retire la classe avant de mesurer.
>
> **Une troisième reste rouge, et c'est documenté dans le code** : « le collet
> s'affine en s'étirant » décrit une intention que `cheminSilhouette` n'a jamais
> implémentée — son pincement ne dépend que du rayon, jamais de l'écart.
> Vérifié aux deux rayons : 12,6 → 12,6 à 8, 20,8 → 20,8 à 4. Le défaut
> préexiste ; la maquette étant un cadre figé, elle ne montre aucun survol et ne
> peut pas trancher. À arbitrer avec le design.

- **Polices, à 1440** : les trois coupes **réellement chargées** — lues sur
  `document.fonts`, jamais sur `fonts.check()`, qui répond vrai sans la
  déclaration puisque la pile de secours lui suffit (éprouvé) —, **aucune
  source tierce** dans les `@font-face`, et la **chasse de Sligoil à 0,6 em**
  aux trois tailles. Cette dernière est celle qui compte : une coupe substituée
  laisse la page lisible et ne se voit nulle part ailleurs, mais fait déborder
  les titres collants de « Conseils » au-dessus de la colonne de droite.

- **Pages intérieures, à 1440** : « Le cabinet », « L'équipe » et « Conseils »
  rejouent la campagne entière — les assertions qui ne trouvent pas leur nœud se
  taisent, celles qui sont communes valent partout. S'y ajoutent leurs cotes
  relevées au pixel : grille de 48 à 1392 et colonne de droite à 726 sur les
  deux premières, tête collée d'un seul tenant sur le cabinet, visuel exactement
  2:1 et chapô de 24/34 sur l'équipe.

- **« Conseils », trente assertions** : les deux grilles de la page — titre à
  48, colonnes à 161 + 440 + 12 + 666 —, le titre **borné avant la colonne de
  droite**, les deux collages et leurs cages, **aucun retrait bas sur les
  sections** (c'est lui qui rend la chasse possible), la hauteur de titre
  publiée par le script et égale à la mesure, l'aplat et ses trois conditions,
  les étiquettes alignées en haut de leur bloc, **un seul filet et seulement
  entre deux groupes de questions**, la coupe `--flush` de l'accordéon, le plan
  de titres qui descend en `h4` seulement quand une étiquette occupe le `h3`, et
  les 22px entre deux paragraphes.

> **Deux assertions de l'accueil ont dû être RECADRÉES, pas assouplies.**
> `.accordion__item` ne désignait plus une seule page dès que « Conseils » a
> repris le composant : les cotes de l'accueil — cinq entrées, filet, 48 de part
> et d'autre — tombaient sur une page qui n'a ni filet ni cinq entrées. Elles
> sont désormais lues sous `.block-treatments`. Et le compte de groupes
> dépliables, écrit en dur à deux, est devenu l'invariant qui vaut partout :
> **aucun accordéon sans son attribut de groupe**.
>
> L'épreuve d'exclusivité, elle, partait d'un état supposé fermé : « Conseils »
> ouvre la première entrée de chaque groupe comme la maquette le dessine, et le
> premier clic la **refermait**. Elle referme maintenant tout avant de
> commencer — elle tombait sur du code correct.

> **Ces cotes rougissent à la moindre refonte de maquette, et c'est voulu.** Les
> cinq assertions du cabinet sont passées au rouge le jour où la maquette a posé
> un `h1` au-dessus des sections : toute la page avait glissé de 48px et le
> titre de section était devenu une pastille. Une campagne qui serait restée
> verte n'aurait rien valu.

### Trois largeurs, dont un vrai 320px

`1440`, `500` et `320`. La largeur est passée à l'**iframe** et non à la
fenêtre, et ce détour n'est pas un caprice : sur macOS, **Chrome plafonne la
fenêtre à ~500px**. Un `--window-size=320,900` produisait une vue de 500 —
mesuré, `clientWidth 500` pour 320 demandés — et le palier 320 de WCAG 1.4.10
n'était donc jamais éprouvé.

### Pourquoi un iframe

`bin/qa/harness.html` charge **le site réel** dans un iframe et pilote son
propre JavaScript. Une page de test qui recopierait le balisage finirait par
divorcer du thème sans que rien ne le signale. L'iframe permet aussi de mesurer
`scrollWidth` contre `clientWidth`, seul moyen fiable de détecter un débordement
horizontal — une capture d'écran ne le montre pas.

> Chrome impose une largeur de fenêtre minimale d'environ **500px** en mode sans
> interface : demander 480 en donne 500. Les assertions affichent la largeur
> réellement mesurée, pour que l'intitulé ne mente pas.

> Chrome ne rend pas toujours la main après `--dump-dom`. Le script attend le
> marqueur de fin dans la sortie puis termine le processus, plutôt que d'attendre
> sa sortie. `timeout` n'existe pas sur macOS, ne pas l'utiliser ici.

### Deux sources d'intermittence, et comment elles ont été traitées

Une campagne qui échoue une fois sur quatre est pire qu'une absence de campagne :
elle apprend à ignorer le rouge. Les deux cas rencontrés :

**Invalidation du cache des assets.** Le cache `realpath` de PHP (120 s par
défaut) peut servir un `mtime` périmé dans un worker Apache persistant : la
nouvelle version n'apparaît pas forcément à la requête qui suit le `touch`.
L'assertion **interroge pendant douze secondes** au lieu de conclure d'un coup.

**Fin d'une transition CSS.** Ne jamais l'attendre : sous `--virtual-time-budget`,
`setTimeout` avance instantanément alors que la transition suit les images
réellement produites. Ni une attente fixe ni un sondage ne sont fiables.
Vérifier la **déclaration** (`transitionProperty`, `transitionDelay`) plutôt que
son aboutissement — c'est ce que le code doit garantir, la fin de l'animation
étant l'affaire du navigateur.

### Le profil Chrome doit être NEUF à chaque passage

`bin/qa-front.sh` crée son profil sous un `mktemp -d` renouvelé à chaque
exécution, et ce n'est pas une précaution d'hygiène : **`front.qa.js` est servi
avec un `Expires` à un mois**, comme tout le JavaScript du site. Un profil
réutilisé rejoue donc l'ancien pilote, et les assertions qu'on vient d'écrire
n'existent tout simplement pas.

Constaté en montant un lanceur rapide à une seule largeur, profil fixe : une
assertion ajoutée ne s'affichait pas, alors qu'elle était bien dans le fichier.
Les feuilles et le script du THÈME, eux, passaient — leur URL porte un `?ver=`
dérivé du `mtime`, qui casse le cache. Le pilote de recette, non.

> À retenir pour tout lanceur ponctuel : `--user-data-dir="$(mktemp -d)"`.

### Trois pièges du temps virtuel, tous rencontrés

Le mode sans interface avance le temps sans produire d'images. D'où :

1. **Le défilement animé n'avance pas.** Toujours `scrollTo({ behavior: "instant" })`
   dans une sonde : le thème pose `scroll-behavior: smooth`, et un `scrollTo`
   ordinaire s'arrête en chemin. Constaté : 564px atteints sur 3477 demandés.
2. **Aucune image n'est produite si personne n'en réclame.** Tout code cadencé
   par `requestAnimationFrame` paraît alors inerte. Le harnais entretient donc
   deux pompes rAF, dans le parent **et** dans l'iframe — les deux sont
   nécessaires, l'iframe seule ne suffit pas.
3. **Le budget s'épuise vite** avec ces pompes, et **les images s'arrêtent
   pour de bon**. Mesuré : 3 à 5 images livrées dans les 600 premières
   millisecondes de campagne, puis **zéro** — y compris après avoir porté
   `--virtual-time-budget` de 8 000 à 60 000, donc le budget n'est pas le seul
   frein. Retirer la borne de temps virtuel ne sauve rien : plus aucun résultat
   n'est produit.

   Deux conséquences pratiques :

   - **`scrollTo` déplace la page mais l'évènement `scroll` n'est pas toujours
     délivré.** La position lue le confirme (0 après un retour en haut) alors
     que le compteur d'évènements reste à zéro. L'émettre à la main
     (`win.dispatchEvent(new win.Event("scroll"))`) éprouve l'arithmétique du
     gestionnaire, ce qui est le sujet, et non la plomberie du navigateur.
   - **Un comportement étranglé par `requestAnimationFrame` n'est pas
     éprouvable du tout.** C'est ce qui a fait déplacer l'arrondi du parcours de
     soin du script vers le CSS, et retirer l'étranglement rAF du suivi de
     l'en-tête — son gestionnaire ne lisant aucune géométrie, il n'avait rien à
     étrangler. Là où l'étranglement est justifié, préférer l'injection d'une
     valeur et la mesure de ce que le CSS en fait.

### Ajouter une assertion

Tout est dans `bin/qa/front.qa.js`. La fonction reçoit la fenêtre de
l'iframe : lire par `win.document` et `win.getComputedStyle`, et construire les
évènements dans ce contexte (`new win.KeyboardEvent(...)`).

Un test qui ne peut pas échouer ne vaut rien : après en avoir écrit un, casser
volontairement le code qu'il surveille et vérifier qu'il passe au rouge.

> **Et vérifier qu'il était VERT avant.** Une assertion rouge pour une autre
> raison rougit aussi sous mutation, et la mutation semble alors concluante.
> Cas rencontré : `trouverRegle` cherchait `.front-page > *:not(…)` quand
> Chrome SÉRIALISE `.front-page > :not(…)` — le `*` redondant devant `:not()`
> est retiré. L'assertion ne trouvait aucune règle, donc échouait quoi qu'on
> fasse au CSS. **Le sélecteur passé à `trouverRegle` est celui du CSSOM, pas
> celui de la source** : en cas de doute, lister les `selectorText` réels avant
> d'écrire l'assertion.

**Et RECONSTRUIRE après avoir restauré le code.** La campagne juge `dist/`, pas
les sources : une épreuve qui remet la source en place sans rejouer
`npm run build` laisse le bundle muté, et la campagne suivante condamne du code
sain. Piège rencontré — un échec attribué une demi-heure au code restauré, alors
que le navigateur exécutait encore la mutation de l'épreuve précédente. Le doute
se lève en exposant l'état interne du gestionnaire (un `dataset` temporaire),
pas en relisant la source.

Corollaire : une épreuve se restaure par une ancre **unique dans les deux sens**.
Remplacer `arret = etape === 0 || …` par `arret = false` produit une ancre
inverse en double, la restauration échoue et la source reste mutée. Vérifier
l'unicité de l'ancre inverse **avant** de muter.

## Homologuer une page contre sa maquette

Une page n'est pas finie quand elle ressemble à la maquette : elle est finie
quand une **campagne la compare à sa place**. Les cinq pas, dans l'ordre, et
aucun n'est facultatif :

1. **Relever la maquette** — voir la section suivante. Les cotes sont des
   nombres, pas des impressions.
2. **Mesurer le rendu** dans le navigateur, au même endroit, et comparer.
3. **Écrire les cotes en assertions** dans `bin/qa/front.qa.js`, sous le bloc de
   la page. Une cote qui n'est pas dans la campagne n'est pas homologuée : elle
   est vraie le jour de la livraison, et plus jamais vérifiée.
4. **Rejouer la campagne aux TROIS largeurs**, pas seulement à 1440.
5. **Casser volontairement la règle surveillée** et vérifier que l'assertion
   rougit — voir « Écrire une assertion qui peut échouer ».

> **Les cotes de maquette ne valent qu'à 1440.** Aucune maquette mobile
> n'existe. À 500 et 320, ce qui est homologué n'est pas une cote mais une
> PROPRIÉTÉ : pas de débordement horizontal, pas de chevauchement, pas de mot
> coupé par une largeur de desktop oubliée, contrastes tenus, plan de titres
> intact.

**Le défaut type, et celui qui a motivé cette section.** Le titre de
« L'équipe » porte une `max-width` d'une demi-colonne : c'est ce que dessine la
maquette, où il tient sur deux lignes. Cette largeur relevée à 1440 est restée
en place quand la page passe à une seule colonne : à 320, elle laissait **106
pixels** à un titre dont le seul mot « Clinique » en demande **154**. Le
navigateur coupait dans le mot, neuf lignes pour six mots. La page avait
pourtant été homologuée — mais **à 1440 seulement**, où la cote est juste.

Deux enseignements, tous deux appliqués :

- Les pages intérieures passent désormais les **trois largeurs**.
- Deux assertions génériques attrapent chacune une classe entière : **aucune
  boîte portant une `max-width` ne laisse un mot déborder**, et **rien du
  contenu ne passe sous la bande de l'application** — celle-ci remonte de 48 sur
  ce qui la précède, et une page dont la dernière section ne réserve pas cette
  hauteur voit ses dernières lignes recouvertes sans que rien d'autre ne le
  signale : ni débordement, ni chevauchement entre frères.
- Pour la première : la coupure seule ne suffit pas à conclure. La coupure seule ne suffit pas à
  conclure Le thème l'autorise à dessein pour une adresse électronique ou
  un titre dans une carte étroite, où la boîte occupe déjà toute la place. C'est
  sa conjonction avec une `max-width` qui dénonce une contrainte de desktop
  oubliée.

> **Trois coupures subsistent à 320 et sont tolérées** : deux titres de carte de
> technologie et un titre d'accordéon sur l'accueil, une adresse électronique
> sur le cabinet. Leurs boîtes ne portent aucune `max-width` — elles occupent
> déjà toute leur place, il n'y a pas de largeur à corriger. L'assertion les
> compte et les affiche, sans rougir.

## Lire la maquette au pixel

Deux retours de recette ont été tranchés non par interprétation mais par
**lecture du PDF de maquette**. La chaîne tient en trois pas et ne demande aucune
dépendance :

```bash
sips -s format png "…/HP_06_Frame 54.pdf" --out /tmp/hp06.png
```

puis le décodeur PNG en Python pur écrit pour la QA des cartes inclinées — une
soixantaine de lignes, `zlib` et `struct` — et de l'arithmétique sur les pixels.
C'est ce qui a établi que les pastilles du pied de page valent (243, 248, 254) et
non du blanc, et que la caisse du bus occupe les deux tiers de sa boîte.

Rendre un glyphe en ASCII, ligne à ligne, est le moyen le plus rapide de comparer
un tracé à sa maquette : deux blocs de 24 lignes côte à côte disent en un coup
d'œil ce qu'aucune cote isolée ne montre.

### Les maquettes de page entière : rendre une BANDE, pas la page

Les PDF des pages intérieures font une seule page de 8 000 à 11 000 points de
haut. Les convertir d'un bloc donne une image illisible, et **`sips` ne sait pas
en découper une région** : son `--cropOffset` est ignoré sur la machine de
développement — trois décalages différents ont rendu la même bande centrale.

Deux outils déjà présents s'en chargent. `pdftoppm` vient de **poppler**, comme
`pdfimages` :

```bash
# Une bande de 1300 points depuis le HAUT de la page, à l'échelle 1:1.
gs -q -dNOPAUSE -dBATCH -sDEVICE=ppmraw -r72 -dFIXEDMEDIA -g1440x1300 \
   -o /tmp/bande.ppm \
   -c "<</PageOffset [0 $((HAUTEUR_PAGE - 1300))]>> setpagedevice" \
   -f maquette.pdf

# Une région précise, à 3× pour en tirer une image utilisable.
pdftoppm -jpeg -jpegopt quality=92 -r 216 \
    -x 144 -y 1356 -W 4032 -H 2016 -f 1 -l 1 maquette.pdf sortie
```

> **Le `PageOffset` de Ghostscript se compte vers le BAS**, à l'inverse du
> repère PostScript : `pageH - bandeH - y` amène la bande voulue dans la
> fenêtre. Le signe contraire ne produit aucune erreur, juste une image
> entièrement blanche.

Le PPM se lit en Python sans dépendance — un en-tête de trois entiers puis des
triplets d'octets, bien plus court que le décodeur PNG. C'est cette chaîne qui a
établi les cotes des pages « Le cabinet » et « L'équipe » : position des
pastilles à 442 et 1204, visuel de 1344 × 672 exactement, teinte de puce
`rgb(4, 139, 140)`.

> **Les cotes d'un texte ne se déduisent pas de sa boîte**, que `pdftotext
> -bbox-layout` donne à la ligne : sa hauteur dépend des glyphes présents. Ce
> qui se compare sans risque, c'est le **pas entre deux lignes** — il vaut
> l'interligne rendu — et la **largeur d'une même chaîne** d'un bloc à l'autre.
> C'est ce rapport, 467,9 contre 311,9 sur « Lorem ipsum dolor sit amet,
> consectetur », qui a établi que le chapô de « L'équipe » vaut 24 et non 16.

## Ce qui n'est pas automatisé

### Le figeage d'une section qui se termine par un rail épinglé

Une telle section doit se figer **à la fin de son rail**, pas au bas de sa boîte.
Le calcul vit dans `initVolets` et ne tourne que sur `resize` — or
`initScrollRails` écoute le MÊME évènement et y désactive l'épinglage, puisque la
campagne force `prefers-reduced-motion`. Émettre un `resize` démonte donc
l'échafaudage avant que le calcul ne le voie ; essayé, et deux assertions du rail
passaient au rouge.

L'`ResizeObserver` serait le seul déclencheur propre à `initVolets`, mais il ne
délivre rien sous `--virtual-time-budget` : la section grandit de 1308 à 2707 et
le rappel ne vient jamais, même après plusieurs ticks. Mesuré.

**La moitié CSS est couverte** — « aucun défilement mort après le rail » vérifie
qu'il ne reste que le chevauchement entre le bas de la réserve et le bas de la
section. La moitié JavaScript est vérifiée **hors campagne**, au relevé :
figeage et fin du rail à `scrollY 2746` tous les deux, écart nul.


- **La conformité visuelle à la maquette.** Elle se contrôle en comparant une
  capture Figma et le rendu, position par position. Utile de mesurer plutôt que
  de juger à l'œil : le bord droit d'un élément, sa hauteur, sa position.
- **Le rendu avec les vraies polices.** Tant que Sligoil et Inter ne sont pas
  auto-hébergées, les largeurs de texte diffèrent de la maquette de quelques
  pixels — c'est attendu, pas un défaut.
- **Les vrais appareils.** Aucune maquette mobile n'existe : le comportement
  mobile est une proposition.
- **La restitution par un lecteur d'écran.** La campagne prouve le balisage et
  les mesures, pas ce qu'annonce VoiceOver, NVDA ou JAWS. Le RGAA exige une
  vérification avec les technologies d'assistance : elle reste à faire.
- **La prise de focus au clavier réel.** `:focus-visible` est apparié par focus
  **programmatique** ; ça ne remplace pas une passe à la main.
- **L'écran de contribution lui-même.** La campagne prouve que les styles y
  arrivent et que les blocs y produisent leur balisage complet ; elle ne prouve
  pas à quoi ça ressemble. L'éditeur n'exécute pas le JavaScript du thème, donc
  les flèches et la barre n'y réagissent pas — c'est le rendu de repli, pas un
  défaut.

## Piège des outils Figma

`get_metadata` **n'est pas exhaustif** : il a renvoyé le cadre `hero` comme
dépourvu d'enfants alors qu'il contient une image de fond et une carte
flottante. Reproductible, ce n'est pas un cache.

**Ne jamais conclure à l'absence de quelque chose depuis `get_metadata`.** Pour
affirmer qu'un élément n'existe pas, passer par `get_design_context` ou une
capture — les deux rendent ce qui est réellement là.

Le connecteur est en outre **limité en nombre d'appels**. Le protocole de relevé
et le cache versionné des maquettes vivent dans
[`../design/figma/README.md`](../design/figma/README.md) : **le lire avant tout
appel à Figma.**
