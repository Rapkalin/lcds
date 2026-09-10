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
- **Parcours de soin, à 1440** : cotes de la grille ; la section mesure
  5 écrans ; la translation est animée ; les deux formules sont éprouvées en
  posant l'avancement à la main sur des fractions qui **prouvent l'arrondi au
  palier** (0,31 doit donner 2 vues et non 1,55) ; la translation de repli
  existe hors de la garde `@supports` ; et le **verrou de molette** — hors
  section le cran n'est pas confisqué, section collée il l'est et porte
  d'exactement une étape, trois crans dans la cadence sont absorbés sans que la
  page bouge, **l'arrivée s'ancre sur le bord franchi** — première carte en
  descendant, dernière en remontant — **sans avancer**, un visiteur déjà au
  milieu n'est **pas** ramené au début, **une traîne d'une seconde ne vaut qu'une
  étape**, un défilement continu de deux secondes franchit le plafond, et les
  **cartes de bout marquent un arrêt** — la traîne ne fait sortir ni par le bas
  ni par le haut, et c'est un second geste qui rend la main.

> Le verrou est éprouvable **sans image d'animation**, contrairement au reste du
> pilotage au défilement : son gestionnaire lit la position de la section et la
> corrige dans le même appel, sans passer par `requestAnimationFrame`. Les
> évènements sont synthétisés (`new win.WheelEvent(…, { cancelable: true })`) et
> c'est `defaultPrevented` qui dit si le geste a été confisqué.
>
> Un évènement synthétisé **ne déclenche pas l'action par défaut** du
> navigateur : il ne fait donc pas défiler la page. L'approche de la section est
> donc rejouée à la main — un `scrollTo` avant la section, puis un autre au-delà
> de son bord, chacun suivi de son cran. C'est ce qui permet d'éprouver
> l'arrivée, seul cas où le geste précède l'épinglage.
>
> Même discipline pour l'arrêt de bout : la carte de bout est posée par une
> **bascule**, jamais par un `scrollTo`. Posée à la main, l'arrêt ne serait pas
> armé et l'assertion ne prouverait rien.
>
> Le second `scrollTo` dépasse le bord de **0,6 étape** : c'est la glissade que
> Chrome ajoute de lui-même après le dernier évènement de molette, et sans elle
> l'épreuve de l'ancrage « sur le bord » passait au vert avec du code fautif —
> il n'y avait rien à rattraper.
- **Cartes de technologie** : aucun aplat peint **sous** un visuel, et l'aplat
  de repli subsiste pour une carte sans visuel — voir
  [`front.md`](front.md#rien-sous-un-visuel--le-liseré-des-cartes-inclinées).
- **Réglages du site** (par WP-CLI) : ACF charge bien le groupe *Navigation*
  **depuis le JSON**, le champ est un `button_group`, ses choix viennent de
  `LcdsDotColor`, et le défaut ACF concorde avec le repli du thème. Ce dernier
  point compte : les deux valeurs par défaut vivent à deux endroits, et si elles
  divergeaient la puce changerait de couleur au premier enregistrement sans que
  personne ait rien choisi. Le groupe est écrit à la main dans `acf-json/` —
  ACF peut refuser un JSON mal formé sans que rien ne le signale côté front.
- **Entrée courante du menu** : la classe est posée à la main — le contenu de
  démonstration n'a pas de page courante — puis la puce est mesurée : 12px,
  « Rouge » par défaut, placée avant le libellé, et **élargissant la pastille de
  20px**. Cette dernière est la seule qui distingue une puce qui pousse d'une
  puce superposée en absolu. La classe de la navigation est ensuite permutée
  pour éprouver que le **réglage pilote vraiment la teinte** : sans ça, une
  propriété jamais lue passerait.
- **Accordéon** : la transition d'ouverture est lue sur la RÈGLE — la campagne
  force le mouvement réduit, où elle est neutralisée — et l'on éprouve à côté
  que le panneau fermé garde bien son `display: none`. Sans quoi il ne se
  fermerait plus jamais.
- **Boutons d'action**, éprouvés **par variante** : les trois primaires portent
  du texte blanc ET un aplat bleu dessous — mesurer la seule couleur du texte
  laisserait passer du blanc sur blanc. Les cinq secondaires portent du texte
  bleu, un fond **transparent** et une bordure de 1px au voile `rgba(0, 56, 122,
  0.3)`. Une assertion commune aux deux passerait sur l'une en masquant l'autre,
  ce qui est exactement arrivé quand elles étaient toutes bleues. Et **aucun
  sélecteur `:visited`** ne subsiste dans la feuille servie — voir l'encadré
  ci-dessous.
- **Le survol du bouton secondaire** est lu sur la RÈGLE : la campagne n'a pas de
  pointeur. Le remplissage y vaut le même voile que la bordure au repos, et la
  bordure s'efface — sans quoi deux voiles à 30 % superposés dessineraient un
  anneau foncé.
- **La silhouette des boutons primaires** : trois tracés non vides, la classe
  `cta--shaped` posée sur chacun, et le `d` du path qui démarre à un rayon du
  bord gauche. Ce dernier point est le seul qui dise que le tracé est calé sur le
  BON conteneur — un tracé calé sur le mauvais cadre reste un tracé.

> **L'aplat des pastilles primaires garde le contraste mesurable.** Vidées au
> profit du seul `<path>`, comme le fait la barre de navigation, elles ont fait
> tomber les six libellés à **1,07:1** dans l'assertion de contraste. Le rendu
> était juste à l'œil — blanc sur la silhouette bleue — mais aucun contrôleur de
> contraste ne voit un SVG, et en mode couleurs forcées c'était du blanc sur
> blanc pour de bon. L'assertion « aplat bleu sous ce blanc » est donc AUSSI la
> garde de la silhouette.
- **Glyphes des informations pratiques** : leur INTÉGRATION, pas leur tracé, qui
  vient du client. Aucun ne déborde de la colonne de 24 — les exports font 26 —,
  tous peignent en `currentColor`, et la colonne porte bien le turquoise du
  jeton.

> **Un défaut qui restera invisible à la recette : `:visited`.**
> `getComputedStyle` ment délibérément sur cet état — c'est une protection de la
> vie privée — et un profil de navigateur neuf n'a de toute façon aucun
> historique. Mesuré : deux captures d'un lien visité et d'un lien jamais visité,
> dans un profil persistant où la cible avait été chargée au préalable, rendent
> **le même blanc**. Le texte bleu que voyait le client était pourtant réel.
>
> Quand l'état est inobservable, l'assertion porte sur la RÈGLE : la feuille
> servie ne doit contenir aucun sélecteur `:visited`. C'est le seul angle qui
> mord.
- **Rail piloté par le défilement** : un seul conteneur d'épinglage sur la page
  — celui de L'HISTOIRE, pas celui des technologies qui partage le composant —,
  la réserve **déclarée** égale à la course (le rapport de 1:1 relevé sur la
  référence), le rail en `overflow-x: hidden`, l'association éprouvée à
  mi-réserve, au bout **et au retour**, et la flèche qui avance la page d'une
  largeur de rail.

> **Deux assertions de ce bloc ne valaient rien, et la mutation les a
> démasquées.** « Réserve = course » et « arrêt de tabulation retiré » restaient
> VERTES avec un script qui publiait la course décalée de 100px et qui ne
> retirait plus le `tabindex` — parce que la campagne posait elle-même ces deux
> états avant de les mesurer. Elle vérifiait son propre échafaudage.
>
> La première est devenue une assertion sur la **règle** : la feuille déclare
> bien `calc(100svh + var(--pin-course))`. La seconde a été **supprimée** ; le
> retrait du `tabindex` est vérifié à la main sur le site réel — mesuré `null`
> une fois l'épinglage actif — et déclaré comme tel plutôt que faussement
> couvert.
>
> Ce que la campagne ne peut pas produire ici, c'est l'état ACTIF par le script
> lui-même : `prefers-reduced-motion` est forcé, et c'est justement l'une des
> sorties de secours de l'épinglage. Elle pose donc l'état et n'éprouve que ce
> qui reste au script — le calcul de l'association.

> **L'épinglage n'est jamais posé de lui-même pendant la campagne** : elle force
> `prefers-reduced-motion`, qui est précisément l'une de ses trois sorties de
> secours. Les assertions du rail natif mesurent donc le REPLI, et c'est aussi
> ce qu'elles éprouvent — la première vérifie explicitement qu'au repos le rail
> garde son `overflow-x: auto` et son `tabindex`. L'état épinglé est ensuite
> FORCÉ, en posant ce que pose le script, pour éprouver la seule chose qui reste
> en jeu : le calcul de l'association. Un réglage de réserve faux ne casserait
> rien d'autre.

- **Volet du hero** : le hero déclaré collé, ses frères déclarés au-dessus, et
  surtout — seule assertion qui porte sur l'état réel — **aucune section suivant
  le hero n'a de fond transparent**. C'est elle qui protège la contribution : les
  sections étant réordonnables, une section sans fond propre placée sous le hero
  le laisserait transparaître. Elle rougit en nommant la section fautive.

- **Coins hauts des sections** : rayon de 48 en haut, francs en bas, **et** le
  chevauchement de 48 qui va avec. Les deux ensemble : sans le chevauchement,
  l'encoche des coins laisse voir le blanc du conteneur, soit deux oreilles
  claires à chaque épaule.

- **Panneaux dépliables** : deux groupes déclarés sur la page, au plus un
  ouvert par groupe, ouvrir un panneau referme son voisin, un second clic
  referme tout, et surtout les deux groupes **n'interfèrent pas** — c'est ce
  qu'un `data-disclosure-group` posé au mauvais niveau casserait en silence.

> L'assertion « au plus un ouvert au chargement » **ne peut pas échouer sur le
> contenu semé**, qui ne coche qu'un panneau par groupe : vérifié, elle reste
> verte quand on retire le code qui l'applique. Gardée comme garde-fou pour un
> contenu futur, et déclarée comme telle dans son commentaire.

- **Rails plein-bord et page bornée** : les deux rails touchent les deux bords,
  la première vignette garde son retrait au repos et sort par le bord gauche au
  défilement. Le bornage à 1920 est lu sur la RÈGLE — la campagne joue à 1440 et
  320, le plafond n'y est jamais atteint. Vérifié à la main en l'abaissant à
  1200 : en-tête, `main`, pied de page et le visuel FIXÉ du pied de page se
  centrent tous.

- **Galerie de l'histoire** : aucune flèche, aucune classe de glissement,
  l'indicateur conservé — plus ses cotes de maquette, course et respiration de
  fin. Le bloc des CONTRÔLES, lui, vise le carrousel des technologies : c'est le
  seul qui ait encore des flèches, et apparier un rail aux boutons d'un autre
  mesurait un attelage inexistant.

> **Le contrôle de contraste passait par chance sur trois familles.** Il
> remontait les ancêtres jusqu'au `body`, le trouvait blanc, et concluait juste
> sans mesurer le bon fond. Il sait désormais lire un fond posé par un
> **pseudo-élément** et reconnaître un élément **couvert par un SVG** ; le lien
> d'évitement, hors écran, est exclu de la boucle et son état FOCALISÉ éprouvé
> à la place. Rien de tout cela n'était couvert avant.

- **Principe de survol** : les trois familles contournées — bouton secondaire,
  flèches de carrousel, icône d'accordéon — remplissent du voile et effacent
  leur bordure. Éprouvées ENSEMBLE, sur une liste de sélecteurs : la règle ne
  souffre pas d'exception, et une exception ajoutée plus tard doit rougir.

- **Liens du pied de page** : pas de soulignement au repos ni au survol, où
  l'épaisseur passe à 600 — les deux ensemble, sans quoi un soulignement
  réintroduit donnerait deux signaux pour un. Et la pile Inter est bien
  HÉRITÉE du `body`, ce qui interdit qu'on la redéclare en chemin.

- **Contribution de la page d'accueil** : un gabarit de `layouts/` par layout
  déclaré et réciproquement, le catalogue porte bien ses six sections, la page
  porte des rangées, `post_content` est vide, et l'éditeur de blocs est coupé
  sur la page d'accueil **mais actif ailleurs**.

> Le bloc précédent bouclait sur `parse_blocks(post_content)`. Après la bascule
> en contenu flexible ce contenu est vide : la boucle ne tournait plus et le
> bloc n'émettait **plus aucune assertion, sans échouer**. Le remplaçant compte
> ses assertions et échoue si le total n'est pas celui attendu. Ce bloc passe par
  WP-CLI et non par le navigateur : ouvrir wp-admin sans interface demanderait de
  fabriquer un cookie d'authentification, hors de proportion pour ce que ça
  prouve.

- **Accessibilité, à chaque largeur** : contraste du texte calculé sur les
  styles réels — une remontée qui écarte non seulement les fonds en image CSS,
  mais aussi les éléments **couverts par un `<img>` en plein cadre**, comme le
  titre d'une carte de technologie. Sans ce cas, la campagne mesurait le titre
  contre l'aplat de repli de la carte, aplat que la photo recouvrait
  entièrement : elle passait en mesurant une couleur que personne ne voit ; `:focus-visible` apparié sur chaque contrôle affiché ;
  attribut `alt` présent sur chaque image ; un seul `h1` et aucun saut de niveau
  de titre ; aucune étiquette de section restée hors du plan de titres ; et rien
  de la page derrière le panneau mobile qui reste tabulable.
- **Accessibilité côté serveur** : aucun composant ne force `'alt' => ''`, et
  aucun gabarit de titre Yoast n'est resté en anglais.

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

## Ce qui n'est pas automatisé

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
  le rail ne défile pas et les étapes du parcours s'y empilent — c'est le rendu
  de repli, pas un défaut.

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
