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

- **Aperçu dans l'éditeur** : la feuille du thème est bien servie à
  l'administration ; chacun des quatre blocs produit un aperçu sans indice de
  bloc vide ; chacun est en `"mode": "auto"` et rend son **formulaire dans le
  canevas** quand il est sélectionné ; et ce formulaire ne fuit **jamais** en
  front. Ce bloc passe par
  WP-CLI et non par le navigateur : ouvrir wp-admin sans interface demanderait de
  fabriquer un cookie d'authentification, hors de proportion pour ce que ça
  prouve.

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
3. **Le budget s'épuise vite** avec ces pompes : **un seul défilement fiable par
   campagne.** Au second, ni évènement ni image. Préférer donc l'injection d'une
   valeur et la mesure de ce que le CSS en fait, plutôt que le pilotage du
   défilement.

### Ajouter une assertion

Tout est dans `bin/qa/header-menu.qa.js`. La fonction reçoit la fenêtre de
l'iframe : lire par `win.document` et `win.getComputedStyle`, et construire les
évènements dans ce contexte (`new win.KeyboardEvent(...)`).

Un test qui ne peut pas échouer ne vaut rien : après en avoir écrit un, casser
volontairement le code qu'il surveille et vérifier qu'il passe au rouge.

## Ce qui n'est pas automatisé

- **La conformité visuelle à la maquette.** Elle se contrôle en comparant une
  capture Figma et le rendu, position par position. Utile de mesurer plutôt que
  de juger à l'œil : le bord droit d'un élément, sa hauteur, sa position.
- **Le rendu avec les vraies polices.** Tant que Sligoil et Inter ne sont pas
  auto-hébergées, les largeurs de texte diffèrent de la maquette de quelques
  pixels — c'est attendu, pas un défaut.
- **Les vrais appareils.** Aucune maquette mobile n'existe : le comportement
  mobile est une proposition.
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
