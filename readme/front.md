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

## Contenu de démonstration — hors dépôt

Tant que la source d'édition n'est pas arbitrée, les gabarits n'ont aucun média à
afficher et rendent des aplats. Deux fichiers **non versionnés** permettent de
regarder le front avec du contenu :

| Fichier | Rôle |
| --- | --- |
| `bin/seed-demo.sh` | Extrait les visuels des PDF de maquette et les importe |
| `website/app/mu-plugins/lcds-local-demo.php` | Les injecte dans les blocs |

```bash
bin/seed-demo.sh            # amorce, ne refait rien si déjà fait
bin/seed-demo.sh --force    # supprime les précédents et recommence
```

Prérequis : `pdfimages` (poppler) et les maquettes. Le dossier est réglable par
`LCDS_MOCKUPS_DIR`. Les identifiants sont enregistrés dans l'option
`lcds_demo_media` — jamais dans le dépôt, un identifiant d'attachement ne voulant
rien dire d'un environnement à l'autre.

### Le thème n'en dépend pas

C'est la condition pour pouvoir les ignorer sans rien casser. `front-page.php`
passe ses blocs par le filtre **`lcds_front_page_blocks`**, et le mu-plugin s'y
branche de l'extérieur. Aucun `require`, aucun appel de fonction : le thème ne
sait pas que cet outil existe.

Vérifié en déplaçant les deux fichiers hors du projet : site en 200, aucune
erreur fatale, 1 aplat de hero et 6 aplats de carrousel à la place des visuels,
`composer check` et la campagne de QA au vert. C'est l'état dans lequel tourne
la CI.

Ce filtre est aussi **la couture par laquelle la future source d'édition
remplira les blocs** : ce n'est pas une prise posée pour la démo seule.

### Deux garde-fous, éprouvés

- **Rien ne s'affiche en production.** `wp_get_environment_type()` retombe sur
  `production` quand rien n'est défini *et* quand la valeur n'est pas l'un des
  quatre types reconnus. Vérifié de bout en bout.
- **Un média supprimé retombe sur l'aplat**, l'identifiant orphelin étant écarté.

> **Contrepartie de l'exclusion : ces deux fichiers disparaissent au prochain
> clone**, et aucun coéquipier ne les reçoit. En garder une copie hors du dépôt
> si tu ne veux pas les réécrire.

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
