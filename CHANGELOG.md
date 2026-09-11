# Journal des modifications

Une section par version. La version fait foi dans **`composer.json`** — c'est
elle que le pied de page du site affiche, via `lcds_site_version()`.

> **À tenir à jour à chaque livraison.** Voir la règle 8 de
> [`CLAUDE.md`](CLAUDE.md) : une version qui bouge sans entrée ici rend le
> journal inutile, et une entrée sans version rend la version fausse.

## 2.19.0

### Ajouté

- **Une section « Application mobile »**, disponible dans le catalogue de la
  page d'accueil comme les autres : le contributeur la place où il veut dans
  l'ordre des sections. Elle porte une étiquette, un titre, un visuel qui
  occupe **toute la largeur de l'écran** et un QR code.

  **L'étiquette et le bouton d'une section ne passent plus derrière le menu.**
  Une section à peine plus haute que l'écran se figeait quelques pixels trop
  haut, et ces pixels disparaissaient entièrement sous le menu fixe : le bouton
  « voir toutes les technologies » s'y cachait. Le collage s'arrête maintenant
  avant, et rien n'est perdu — ces pixels étaient du rembourrage.

  **On lit « informations pratiques » jusqu'au bout avant qu'elle ne laisse la
  place.** Une section ne se fige désormais que si la suivante peut la
  recouvrir : la dernière entrée de la section restait sinon lisible 350px
  seulement, quelle que soit la hauteur de l'écran, l'image venant la manger
  sur place. Elle l'est maintenant sur 550 à 1050px selon l'écran, et sort par
  le haut à son rythme.

  **Sa hauteur vient de son visuel**, et non de l'écran comme les autres
  sections : elle vaut la hauteur naturelle du visuel en pleine largeur, ou
  celle du contenu si celui-ci est plus haut. Le visuel remplit la bande dans
  les deux cas. Conséquence assumée : plus courte qu'un écran, elle ne joue pas
  le volet — elle défile, et c'est la section suivante qui vient la recouvrir.

  Le QR code accepte un lien, et c'est ce qui le rend utilisable : un code
  affiché à l'écran ne peut pas être scanné depuis l'appareil qui l'affiche.
  Renseigné, le code devient un lien vers la même destination. Il reste
  facultatif : un contributeur dont la photo intègre déjà le code laisse le
  champ vide.

  Le titre est écrit en bleu foncé sur la photo : **prévoir une zone claire à
  gauche**. Mesuré sur le visuel de la maquette, le pire endroit du titre est à
  8,67:1. Un voile de protection avait été ajouté puis retiré sur retour
  client — il se voyait.

### Corrigé

- **La recette ne compte plus les sections en dur.** Elle exigeait « six
  sections » : elle rougissait donc mécaniquement à chaque section ajoutée,
  sans rien dire de plus que la comparaison entre le catalogue et les gabarits
  qui la précède.

## 2.18.2

Audit performance et RGAA de la page d'accueil. Aucune couleur n'a été touchée.

### Corrigé

- **833 Ko d'images ne sont plus téléchargés à l'ouverture de la page.** Vingt
  visuels situés sous la ligne de flottaison — carrousel, parcours de soin,
  technologies, informations pratiques — partaient sans consigne de chargement
  différé, et le navigateur les récupérait tous d'emblée. Seuls la bannière et
  la vignette de sa carte, réellement visibles au chargement, restent
  prioritaires.
- **Les cinq sections de l'accueil sont désormais des régions nommées.** Une
  section sans nom accessible n'apparaît pas dans la navigation par régions
  d'un lecteur d'écran : on n'y trouvait que l'en-tête, la navigation, le
  contenu et le pied de page. Chacune est maintenant annoncée par son titre.
- **Le focus ne se pose plus sur une commande invisible.** Une section figée
  par le volet est entièrement recouverte par les suivantes, mais ses liens et
  ses boutons restaient accessibles au clavier : la tabulation y menait sans
  que rien ne s'affiche. Mesuré : six commandes concernées sur la section des
  traitements. La page revient maintenant sur la section avant que le focus s'y
  pose.
- **Ce que le navigateur amène dans la vue ne passe plus sous l'en-tête fixe.**
  Une ancre, un lien d'évitement ou une commande qui prend le focus se posait
  derrière lui. Vaut aussi pour les ancres à venir.

## 2.18.1

### Corrigé

- **Plus de saut à la jonction entre le parcours de soin et la section
  suivante**, dans les deux sens. Une fois la dernière étape atteinte,
  l'animation s'arrête et le volet suivant passe par-dessus comme à toutes les
  autres jonctions. Le premier cran de molette vers le haut renvoyait jusque-là
  la page 650px en arrière, sous le volet — mesuré.

## 2.18.0

### Modifié

- **La bannière d'accueil occupe toujours toute la hauteur de l'écran.** Elle
  était calée sur un rapport 16/10 plafonné à 900px : sur un écran plus haut,
  elle s'arrêtait avant le bas et laissait voir la section suivante sous elle.
  La carte « Prendre RDV » reste à 24px du bas quelle que soit la hauteur —
  vérifié de 700 à 1400. Écart assumé avec la maquette, qui la dessine en
  1440 × 900.

## 2.17.0

### Ajouté

- **Chaque section de l'accueil passe par-dessus la précédente comme un volet**,
  et plus seulement la première sous la bannière. Une section se fige quand on
  a fini de la lire — son bas atteint le bas de l'écran — et la suivante remonte
  alors par-dessus elle. L'effet suit l'ordre choisi dans l'administration et
  vaut pour n'importe quelle section. Désactivé pour qui demande à réduire les
  animations.
- **Chaque volet occupe au moins toute la hauteur de l'écran.** Sans ce
  plancher, une section plus courte que la vue se figeait en laissant voir la
  précédente au-dessus d'elle : elle ne la recouvrait jamais. Les sections plus
  hautes qu'un écran ne sont pas touchées, leur fin reste atteignable.
- **Une ombre porte l'arête du volet.** Sans elle l'arrondi ne se voit pas :
  quatre des cinq sections de l'accueil ont exactement la même couleur de fond,
  et un bord rond ne se lit que contre une autre couleur. Ajout hors maquette,
  à faire valider par le designer.

### Corrigé

- **Les coins arrondis reviennent sur la section sous la bannière.** Elle les
  avait perdus en 2.16.2, pour éviter deux oreilles blanches à ses épaules.
  C'est maintenant le visuel de la bannière qui descend de 48px sous elle et
  peint derrière ces épaules : l'arrondi est rétabli, les oreilles ne
  reviennent pas, et rien ne chevauche rien — la section suivante reste
  exactement où elle était.

## 2.16.2

### Corrigé

- **Plus d'oreilles blanches sous la bannière d'accueil.** La section qui la
  suit portait des coins hauts arrondis alors qu'elle n'a rien au-dessus
  d'elle : sur une vue plus haute que la bannière, ses deux épaules laissaient
  voir le blanc de la page pendant les 48 premiers pixels de défilement.
  Mesuré à 1440 × 1100 : le point situé quatre pixels sous la bannière peignait
  du blanc. Cette section garde désormais un bord franc — c'est d'ailleurs ce
  que la maquette dessine à cet endroit, et c'est le seul bord de section qui
  ne borde jamais une autre section. Les suivantes gardent leurs coins
  arrondis.
- **Le parcours de soin repart plus vite d'une étape à l'autre** : le
  défilement n'est plus absorbé que 400 ms au lieu de 500, la transition du
  rail passant de 0,45 s à 0,35 s. Les deux vont ensemble — l'absorption ne
  peut pas descendre sous la durée de la transition sans que l'étape suivante
  parte avant que la précédente soit posée.
- **Le parcours de soin ne peut plus rester figé** quand une image d'animation
  n'est jamais produite — onglet en arrière-plan, par exemple. La mise à jour
  est redemandée au lieu d'être abandonnée, sans changer le regroupement des
  évènements.

### Pour les contributeurs

- **Le repli de la couleur de puce de la page courante vit à un seul endroit**,
  `lcds_nav_dot_fallback()`. La recette éprouve désormais sa concordance avec
  le défaut du champ ACF, au lieu de lire le réglage enregistré : elle passait
  au rouge dès qu'un contributeur avait choisi « Vert », alors que rien n'était
  cassé.

## 2.16.1

### Corrigé

- **Les numéros d'étape du parcours de soin reprennent la typographie et la
  couleur de la maquette** : Sligoil en 24, en turquoise, là où ils portaient le
  style des boutons — Inter en 13, en bleu, avec un fort interlettrage. Ils
  restent alignés sur la première ligne du titre de l'étape.

  Leur taille ne diminue plus sur les écrans étroits, contrairement aux titres :
  le turquoise n'est lisible sur le panneau qu'à partir de 24px, seuil au-delà
  duquel un texte est considéré comme grand.

## 2.16.0

### Ajouté

- **L'étiquette des sections « les traitements » et « informations pratiques »
  reste au même niveau pendant le défilement** : seule la colonne de droite
  bouge. Elle se décroche à la fin de la section, quand celle-ci laisse la
  place à la suivante.

  Sur « informations pratiques », la colonne de gauche porte aussi le visuel :
  l'ensemble mesure 626px et ne peut donc rester immobile que sur les **38
  premiers pourcents** de la section, faute de course suffisante. Sur « les
  traitements », où l'étiquette est seule, elle tient tout du long.

## 2.15.0

### Ajouté

- **La section qui suit la bannière d'accueil remonte par-dessus elle**, comme
  un volet qui la referme peu à peu. Peu importe quelle section est placée là :
  l'effet suit l'ordre choisi dans l'administration, et vaut pour celle qui s'y
  retrouvera si l'ordre change. Désactivé pour qui demande à réduire les
  animations.
- **Les sections de la page d'accueil ont les coins du haut arrondis**, et
  s'emboîtent comme des panneaux empilés — sauf la première sous la bannière,
  qui arrive après elle sans la recouvrir. Écart assumé avec la maquette, qui
  les dessine à angles francs.
- **Les carrousels ne s'arrêtent plus au bord gauche.** La première image garde
  sa place à l'arrivée ; au défilement, les visuels sortent par le bord de la
  page comme ils le font déjà à droite. Vaut pour la galerie de « l'histoire »
  et pour les cartes de technologies.

### Modifié

- **La galerie de « l'histoire » n'a plus de flèches.** Elle avance avec le
  défilement de la page. Le glisser-déposer à la souris disparaît avec elles :
  les flèches en étaient l'alternative accessible, et un geste sans alternative
  ne doit pas exister. L'indicateur d'avancement, lui, reste.
- **Un seul panneau dépliable reste ouvert à la fois.** Déplier une entrée de
  l'accordéon des traitements referme la précédente, et les cartes de
  technologie se comportent de même entre elles. Les deux ensembles restent
  indépendants : ouvrir une carte ne ferme pas l'accordéon. La règle vaut aussi
  à l'ouverture de la page, si plusieurs panneaux sont cochés « ouvert ».
- **Les flèches des carrousels et les boutons d'accordéon réagissent au survol
  comme les boutons secondaires** : un voile bleu remplit la pastille, au lieu
  du fond blanc qu'ils prenaient.
- **Les liens du pied de page ne se soulignent plus au survol** : le texte
  s'épaissit à la place. Vaut pour la navigation comme pour les liens légaux.

### Corrigé

- **Le parcours de soin repart plus vite d'une étape à l'autre.** Après une
  bascule, le défilement en trop était absorbé trop longtemps : l'attente tombe
  de 1,6 à 1,2 seconde au pavé tactile, et de 0,6 à 0,5 seconde à la molette. Le
  reste ne bouge pas — plusieurs crans rapprochés valent toujours une seule
  étape.
- **La photo révélée en bas de page disparaissait** derrière un aplat, à cause
  du travail sur la largeur maximale. L'ordre d'affichage est désormais déclaré
  explicitement au lieu de dépendre d'une subtilité de peinture.

### Pour les contributeurs et les développeurs

- **L'accordéon est un composant réutilisable**, comme l'étaient déjà toutes les
  sections. Le poser sur une autre page ne demande qu'un appel avec ses
  arguments — voir [`readme/contribution.md`](readme/contribution.md).
- **Une largeur maximale de page existe mais est SUSPENDUE.** Le dispositif est
  en place et borne l'en-tête, le contenu et le pied de page ensemble ; sa
  valeur est réglée sur la largeur de la vue, donc sans effet. Une seule valeur
  à changer pour l'activer.
- Le contrôle de contraste de la recette **passait par chance** sur trois
  familles d'éléments : il ne savait lire ni un fond posé par un pseudo-élément,
  ni un fond peint par un SVG, et mesurait le lien d'évitement là où il n'est
  jamais visible. Corrigé, et le lien d'évitement gagne une couverture qu'il
  n'avait pas.

## 2.14.0

### Ajouté

- **Les boutons d'action principaux se comportent comme le menu au survol.**
  Le glyphe et le libellé forment une silhouette continue dont le collet s'étire
  quand le curseur arrive, avec le même ressort que les entrées du menu.
- **La galerie de la section « l'histoire » défile avec la page.** Arrivé sur
  elle, le défilement fait avancer les visuels horizontalement, puis reprend son
  cours normal une fois le dernier atteint. Les flèches continuent de naviguer.
  L'effet ne s'applique ni sur mobile, ni pour qui demande à réduire les
  animations : la galerie y reste un carrousel ordinaire.

### Corrigé

- **Les boutons secondaires sont de nouveau contournés**, sur fond transparent
  et texte bleu — les quatre du pied de page et le « voir le plan » des
  informations pratiques. Ils reviennent au dessin de la maquette : la pastille
  bleue à texte blanc de la version précédente est abandonnée, du blanc étant
  illisible sur un fond transparent. Au survol, un voile bleu remplit la
  pastille.
- **Le pied de page annonçait une teinte de texte inexacte** dans le journal de
  la version précédente : le relevé donne le bleu du thème, pas `#143776`.

### Pour les contributeurs et les développeurs

- Les deux variantes de bouton d'action ont désormais une **source unique** côté
  code, qui réconcilie les noms de la maquette et ceux de la feuille de style.
  Aucun champ ne change dans l'administration : la variante reste imposée par
  l'emplacement.
- La durée de fondu commune à tout le site, jusqu'ici recopiée en douze
  exemplaires, vit en un seul endroit.

## 2.13.0

### Ajouté

- **La couleur de la puce de la page courante se règle dans « Réglages →
  Configuration ».** Deux choix, « Vert » et « Rouge », les mêmes que pour les
  puces d'étiquette de section. **« Rouge » par défaut** — la puce était verte.

### Corrigé

- **Tous les boutons d'action portent du texte blanc.** Ceux du pied de page et
  le « voir le plan » des informations pratiques avaient du texte bleu : ils
  prennent désormais la même pastille bleue que les autres. C'est un écart
  assumé avec la maquette, qui les dessine contournés — le relevé au pixel de la
  livraison précédente portait sur leur remplissage, pas sur leur texte.
- **Le texte restait bleu sur un lien déjà visité.** Une règle de style
  neutralisait la couleur des liens visités en l'emportant sur celle des
  composants ; elle est retirée. Elle était inutile.
- **Les cinq pictogrammes des informations pratiques sont ceux fournis par le
  client**, un par fichier. Ils prennent la couleur définie par la feuille de
  style au lieu d'une teinte figée, et tiennent dans la colonne prévue.

## 2.12.0

### Ajouté

- **L'entrée du menu correspondant à la page affichée porte une puce turquoise**,
  comme l'étiquette d'une section.
- **Les carrousels se tirent à la souris.** Les images avancent au
  glisser-déposer et plus seulement aux flèches, qui restent en place. Le geste
  tactile est laissé au défilement natif, qui porte déjà l'inertie ; un
  glissement terminé sur le bouton d'une carte n'en ouvre plus le panneau.
- **Le menu principal suit le défilement** et reste atteignable partout dans la
  page, y compris tout en bas. Il reste transparent sur toute la hauteur, comme
  la maquette le dessine : les pastilles blanches des liens suffisent à les
  rendre lisibles sur ce qui défile derrière.

### Corrigé

- **Le texte des accordeons « traitements » apparaît en fondu** au lieu de
  surgir d'un coup. Sous préférence de mouvement réduit, la bascule reste
  instantanée.
- **Les boutons contournés du pied de page laissent voir le panneau bleu.** Ils
  portaient un fond blanc qui y tranchait — la maquette les veut sans fond, ce
  qu'un relevé au pixel a confirmé.
- **Le pictogramme « transports » n'est plus écrasé** et le repère d'adresse
  n'est plus perdu au milieu de sa boîte : leurs proportions suivent désormais
  celles de la maquette.
- **La photo du bas ne bouge plus au défilement.** Elle est désormais fixée au
  bas de l'écran et c'est le panneau bleu qui remonte pour la découvrir, comme un
  volet — avant, tout défilait ensemble et seule la frontière se déplaçait. Sous
  préférence de mouvement réduit, la photo défile avec la page comme auparavant :
  un fond qui ne suit pas le contenu est un effet de parallaxe.
- **Le parcours de soin avance d'une carte par cran de molette, et tient chaque
  carte le temps qu'il faut.** La section demandait un écran de défilement
  complet par carte — six écrans pour six cartes, d'où un glissement très
  lent —, et le rail pouvait s'arrêter entre deux cartes, qui paraissaient alors
  très écartées.

  Désormais un cran de molette avance d'exactement une carte, quelle que soit
  l'amplitude du geste, et tout ce qui arrive pendant la bascule est absorbé :
  plusieurs coups de molette rapprochés valent un seul, et l'inertie d'une
  lancée de pavé tactile ne fait plus passer deux cartes à la fois.

  Les cartes de bout marquent un **arrêt**, à l'entrée comme à la sortie : la
  première carte est tenue le temps que le geste d'arrivée s'éteigne, puis un
  second coup de molette repart — et symétriquement sur la dernière avant de
  quitter la section. Sans cela on démarrait sur la deuxième carte et la dernière
  n'apparaissait qu'un instant, le geste en cours étant compté comme un nouveau
  coup de molette.

  Chaque carte est donc tenue le même temps, la première et la dernière
  comprises. Un défilement continu avance d'une carte toutes les 1,6 seconde,
  pour que la section ne puisse pas devenir un cul-de-sac. Le clavier, la barre de
  défilement et le geste tactile ne sont pas touchés, et sous préférence de
  mouvement réduit les étapes s'empilent comme avant — rien n'y est confisqué.
- **Plus de liseré autour des visuels inclinés des technologies.** L'aplat de
  repli des cartes affleurait au bord anticrénelé du découpage et y dessinait un
  pointillé bleu foncé d'un pixel. Il ne sert plus qu'aux cartes dépourvues de
  visuel, qui gardent donc leur fond lisible.

## 2.11.0

### Ajouté

- **L'écran des connexions est paginé, 20 par page.** Les liens n'apparaissent
  que s'il y a plus d'une page, et un numéro de page hors bornes retombe sur la
  dernière plutôt que d'afficher une page vide.

## 2.10.1

### Corrigé

- **La dernière entrée du menu anime de nouveau ses voisines.** Elle avait été
  entièrement figée, alors que seul le côté du bouton « Prendre RDV » devait
  l'être : au survol, elle écarte sa voisine de gauche et ouvre son collet
  comme les autres, sans rien ouvrir ni déplacer du côté du bouton.

## 2.10.0

### Ajouté

- **Une colonne « Dernière connexion » sur la liste des comptes**, tirée du
  journal des connexions. Un compte jamais connecté est annoncé comme tel aux
  lecteurs d'écran, et non signalé par un simple tiret. La colonne n'est pas
  triable : le journal n'est pas une méta de compte.

## 2.9.0

### Ajouté

- **Un écran « Comptes → Connexions »** qui liste qui s'est connecté et quand,
  avec le rôle du compte. Réservé aux administrateurs. Aucune adresse IP n'est
  conservée, et les entrées sont supprimées au-delà de 90 jours ou de 200
  connexions. L'écran renseigne mais ne fait pas foi : voir la limite décrite
  dans `readme/roles.md`.

## 2.8.0

### Modifié

- **Seuls deux rôles sont attribuables** : Administrateur et Contributeur LCDS.
  Les six autres restent déclarés — les retirer casserait un compte qui les
  porte — mais ne sont plus proposés. Le rôle d'un compte en cours d'édition
  reste dans la liste, sinon l'enregistrement le changerait en silence.
- **« Mon compte » est réduit à l'utile pour un contributeur** : couleurs de
  l'interface, barre d'outils, identifiant, prénom, nom, pseudo, e-mail et la
  gestion du compte. Le reste est retiré, y compris les sections ajoutées par
  les extensions. Un administrateur qui modifie un contributeur garde le
  formulaire entier.

## 2.7.3

### Corrigé

- **Un refus d'inventaire Watcha n'accuse plus `composer.lock` à tort.** Le même
  code 422 couvre deux causes opposées : Watcha n'a pas vu le fichier, ou il l'a
  reçu et refuse son contenu. Le journal les distingue désormais, et dit quand
  la cause est sur le serveur de veille plutôt que dans la CI.

### Documentation

- `readme/deploiement.md` documente l'envoi d'inventaire à Watcha : les deux
  réglages, le jeton par environnement, la lecture d'un refus, et une sonde qui
  nomme la cause en une requête quand le fichier n'est pas vu.

## 2.7.2

### Corrigé

- **Le déploiement n'échoue plus sur la version de PHP en ligne de commande.**
  WP-CLI était lancé avec le `php` par défaut du serveur, qui n'est pas celui
  réglé au panneau de l'hébergeur : l'autoloader de Composer, construit pour
  PHP 8.4, refusait de se charger. Le déploiement choisit désormais lui-même un
  binaire ≥ 8.4, et le dit dans le résumé du run s'il n'en trouve aucun — auquel
  cas l'amorçage des menus et les purges de cache sont sautés au lieu d'échouer
  en silence.

## 2.7.1

### Corrigé

- **Plus de bande vide entre la dernière section et le pied de page.** Le
  panneau était descendu d'une hauteur de visuel pour le masquer, ce qui
  laissait 513px de vide au-dessus de lui pendant presque tout le défilement.
  Il reste désormais posé là où le contenu s'arrête ; c'est son bas peint qui
  déborde puis se rétracte pour découvrir le visuel.
- **La première carte du carrousel Technologies n'est plus rognée.** Une carte
  inclinée déborde de 11,5px de chaque côté de sa boîte : les autres cartes
  portent ce débord sur leurs voisines, la première n'a personne à sa gauche.

## 2.7.0

### Modifié

- **La barre de navigation est désormais une seule forme.** Les pastilles
  blanches et les collets qui les relient sont tracés d'un même trait, peint
  derrière les liens : la silhouette est continue, sans couture ni interstice à
  la rencontre d'une pastille et de son collet. Le collet s'accroche à
  l'arrondi et en repart sans angle, comme sur la référence.
- **Le survol ne repeint plus la pastille** : c'est l'écartement et le collet
  qui le signalent. Sous `prefers-reduced-motion`, où il n'y a plus
  d'écartement, le libellé est souligné.

## 2.6.1

### Corrigé

- **La dernière entrée du menu ne s'écarte plus au survol.** Elle borde le
  bouton « Prendre RDV », qui n'appartient pas au menu et ne bouge jamais : elle
  poussait donc contre un voisin immobile. Elle reste déplacée par ses voisines,
  mais n'écarte personne.

## 2.6.0

### Ajouté

- **Chaînon entre deux entrées de menu écartées** : un bloc blanc de la hauteur
  des pastilles, aux arêtes haute et basse légèrement creusées — l'image du
  collet d'une dent. Il s'ouvre avec l'écart, sur les mêmes courbes, et n'existe
  pas aux extrémités de la barre.

## 2.5.0

### Ajouté

- **Animation de la navigation** : l'élément survolé écarte ses voisins de 20px,
  avec un dépassement à l'entrée et un retour net à la sortie. Reprise de la
  référence client (floema.com), à deux écarts près : elle joue aussi à la prise
  de focus clavier, et se suspend sous `prefers-reduced-motion`.

## 2.4.1

### Corrigé

- **La page d'accueil tombait en 500 quand ACF n'était pas actif.**
  `front-page.php` appelait `have_rows()` sans garde ; ACF Pro étant sous
  licence et hors du dépôt, tout environnement où il n'est pas installé rendait
  une page blanche. Le site se dégrade désormais : en-tête et pied de page
  rendus, sections absentes.
- **Seconde fatale latente** au même titre dans `inc/contacts.php`, qui aurait
  tué la soumission du formulaire au lieu de retomber sur l'adresse du `.env`.

## 2.4.0

### Ajouté

- **Rôle « Contributeur LCDS »** : pages, médias, menus, personnalisateur,
  configuration du site et son propre profil. Tout le reste est retiré du menu
  **et refusé à l'accès** — masquer une entrée ne protège rien, l'URL reste
  tapable. Voir [`readme/roles.md`](readme/roles.md).
- **Écran *Réglages → Configuration* ouvert par une capacité dédiée**, pas par
  `manage_options` — qui aurait ouvert les sept écrans de Réglages du cœur et
  l'éditeur brut des options en base.

### Changé

- **La boîte SEO passe sous les blocs de contribution.** Yoast s'enregistrait en
  priorité haute : le référencement s'affichait avant le contenu de la page.
- **Le tableau de bord se limite à « D'un coup d'œil ».**

## 2.3.1

### Corrigé

- **Les aides des champs ne renvoient plus au code.** Deux d'entre elles
  nommaient un fichier source — un chemin que personne n'ouvre en éditant le
  site, et qui devient faux au premier renommage.

## 2.3.0

### Corrigé

- **RGAA 10.4 — le texte à 200 % rendait la page illisible sans défilement
  horizontal.** Toutes les cotes du thème étant en `rem`, la mise en page
  doublait avec le texte : la page passait à 1519px pour une vue de 1440. Elle
  tient désormais à 1440, et une assertion de la campagne le vérifie.
- **Les boutons contournés s'inversent au survol** : fond bleu, texte blanc.
  Ils restaient bleus sur blanc.

### Ajouté

- **Audit RGAA 4.1 complet** dans `readme/accessibilite.md`, mené sur la grille
  officielle : 36 critères non applicables sur preuve structurelle, 28 vérifiés
  conformes, 1 non conforme (12.1 — un seul système de navigation), 6 hors de
  portée d'une vérification automatique.

## 2.2.1

### Corrigé

- **Le visuel révélé était 32px trop haut.** Sa boîte fait un rayon de plus que
  ce qu'on découvre — le débord passe sous le panneau — et il était donc centré
  sur la boîte plutôt que sur la partie réellement vue. Le décalage est borné :
  sur une vue étroite la photo n'a plus de débord vertical, et le compenser
  quand même redécouvrait l'encoche.

## 2.2.0

### Ajouté

- **Le cadrage du visuel révélé est contribuable** : haut, centre ou bas. Le
  visuel étant rogné pour remplir la largeur, la bande qu'il montre relève du
  contenu et non du gabarit.

### Changé

- **L'écran de configuration passe sous *Réglages → Configuration***, au lieu
  d'une entrée de premier niveau dans le menu d'administration.

## 2.1.1

### Corrigé

- **Liseré blanc dans les coins du pied de page.** Le visuel démarrait là où le
  panneau s'arrête ; les encoches de ses coins arrondis laissaient donc voir le
  fond du bloc. Le visuel remonte désormais d'un rayon sous le panneau, à tout
  avancement de l'animation.

## 2.1.0

### Ajouté

- **Pied de page**, relevé au pixel sur le PDF de maquette — trois blocs
  d'appel, adresse, deux menus, logo et mention de copyright.
- **Sa révélation** : le panneau masque un visuel pleine largeur et se soulève
  en fin de page pour le découvrir. Désactivée sous `prefers-reduced-motion`,
  où le visuel reste simplement visible.
- **Écran « Réglages du site »** (page d'options ACF) : le pied de page est
  commun à toutes les pages, son contenu n'appartient à aucune d'elles. Sa
  structure est versionnée en JSON local comme le reste.
- **Les menus du pied de page sont amorcés** — navigation et liens légaux.

## 2.0.1

### Corrigé

- **La configuration des champs ne peut plus diverger du dépôt.** L'interface de
  gestion des groupes ACF est masquée hors développement, et les clés propres à
  la machine — dont `local_file`, un chemin absolu — sont retirées du JSON après
  chaque écriture. Sans ça le fichier différait d'une machine à l'autre sans
  qu'aucun champ n'ait bougé.

## 2.0.0

**Rupture de contribution.** La page d'accueil ne se contribue plus en blocs de
l'éditeur mais par un **champ de contenu flexible**, sur le modèle de 2bdm. Un
contenu saisi avant cette version doit être ressaisi : il vivait dans
`post_content`, il vit désormais en post meta.

### Changé

- **Les six sections de la page d'accueil sont les layouts d'un unique champ**
  `sections`, dans un seul formulaire sous l'éditeur. Elles s'ajoutent, se
  réordonnent et se suppriment au glisser-déposer.
- **Le catalogue est scellé à la page d'accueil** (`page_type == front_page`).
  Aucune de ces sections ne peut atterrir sur une autre page — c'était le
  principal défaut du modèle en blocs.
- **L'éditeur de blocs est coupé page par page**, pas globalement : seuls les
  contenus listés par `lcds_acf_contributed_posts()` le perdent. Le texte libre
  continue de s'écrire en blocs.
- Le titre `h1` est devenu un champ de la **page** et non de sa première
  section.
- Les champs restent **versionnés en JSON local**, contrairement à 2bdm où ils
  ne vivent qu'en base.

### Retiré

- Les six blocs `acf/lcds-*`, `inc/blocks.php`, la catégorie d'insérateur, et
  l'aperçu dans le canevas — avec les `add_editor_style` et les assertions de QA
  qui allaient avec.

## 1.1.0

Première version qui expose sa propre version.

### Ajouté

- **Version du site dans le pied de page**, lue dans `composer.json` — source
  unique. `composer.json` part désormais avec l'artefact de déploiement : il
  vit hors du docroot, donc Apache ne le sert pas.
- **Contribution de la page d'accueil en blocs de l'éditeur** : six sections
  déclarées comme blocs ACF (`acf/lcds-*`), champs versionnés en JSON local,
  titre `h1` contribuable — voir [`readme/contribution.md`](readme/contribution.md).
- **Sections de la page d'accueil** : en-tête, hero, texte et galerie,
  accordéon des traitements, parcours de soin défilant, carrousel de cartes
  inclinées, informations pratiques.
- **Conversion WebP native** via `image_editor_output_format`, sans plugin —
  voir [`readme/images.md`](readme/images.md).
- **Menus versionnés** : emplacements, rattachement et **entrées par défaut**
  amorcés par le code, sur tous les environnements. Sans ça un déploiement neuf
  sortait un en-tête sans navigation.
- **Campagne de QA du front** : assertions jouées dans un navigateur sans
  interface, à 1440, 500 et **320px** — voir [`readme/qa.md`](readme/qa.md).
- **Page d'accessibilité** documentant les décisions et les contrastes mesurés
  — voir [`readme/accessibilite.md`](readme/accessibilite.md).

### Corrigé

- **Accessibilité** : les 16 images de la page d'accueil sortaient en `alt=""`
  d'office ; les libellés de section n'étaient pas des titres ; `index.php`
  produisait plusieurs `h1` sur une liste ; le panneau mobile laissait onze
  contrôles tabulables derrière lui ; le bouton d'action était à 3,84:1 pour un
  seuil de 4,5.
- **Gabarits de titre de Yoast** restés en anglais : son paquet de langue
  n'était pas installé au moment de son activation — voir
  [`readme/seo.md`](readme/seo.md).
- **Invalidation du cache des assets** : CSS et JS étaient servis avec la
  version de WordPress sous un `Expires` d'un mois.
- **Amorçage de la page d'accueil joué avant l'activation d'ACF** : les blocs
  partaient sans aucune donnée, et l'idempotence interdisait la correction.
- **Débordement horizontal** et coupe de la carte du hero sur les vues basses.

## 1.0.0 — périmètre initial, non livré

Liste de cadrage conservée telle quelle : ce sont des tickets de périmètre, pas
des livraisons.

- [LCDS-1] - Header
- [LCDS-2] - Footer
- [LCDS-3] - Homepage
- [LCDS-4] - Le cabinet
- [LCDS-5] - L'équipe
- [LCDS-6] - Cas cliniques
- [LCDS-9] - Contact pages
- [LCDS-10] - Foire aux questions
- [LCDS-10] - Legal mentions
- [LCDS-12] - Mobile version
- [LCDS-13] - Optimisation and performance
- [LCDS-14] - Website animations
- [LCDS-17] - 404 page
