# Menus de navigation

Les emplacements de menu et leur création vivent dans le thème :

| Fichier | Rôle |
| --- | --- |
| `inc/enums/LcdsMenuLocation.php` | **Source unique de vérité** : un cas = un emplacement |
| `inc/menus.php` | Enregistrement des emplacements + création automatique |
| `inc/navigation.php` | **Rendu** : un helper par menu affiché |

## Création automatique

Un contributeur ne doit jamais tomber sur l'écran « Créez votre premier menu »,
ni avoir à saisir la navigation d'un site qu'on vient de lui livrer. Chaque
emplacement déclaré reçoit un menu, rattaché automatiquement, **garni des
entrées portées par l'enum**.

Le mécanisme est gardé par une option (`lcds_menus_seed_version`) : passé le
premier passage, il se réduit à une lecture d'option. **Le front n'est jamais
ralenti.** Il est déclenché de trois façons :

| Déclencheur | Quand |
| --- | --- |
| `admin_init` | à la première requête d'administration |
| `bin/init.sh` | au démarrage d'un environnement local |
| `deploy.yml` | juste après un déploiement, par WP-CLI |

Les trois sont nécessaires. Accroché au seul `admin_init`, le site n'aurait pas
de navigation avant qu'un administrateur ouvre le back-office.

### Pourquoi les entrées sont versionnées

`wp_nav_menu()` est appelé avec **`fallback_cb => false`** : un menu vide ne rend
donc **rien du tout**, pas même la liste des pages. Sans entrées par défaut, un
environnement fraîchement déployé sort un en-tête **sans navigation**.

Ce n'était pas une hypothèse : les entrées de la navigation n'ont longtemps
existé que dans une base locale, saisies à la main et invisibles du dépôt. En
les supprimant, le front rendait `site-nav__list` **zéro fois**.

Les destinations valent `#` : les pages cibles n'existent pas encore. Une URL
vide est *acceptée* par `wp_update_nav_menu_item()` — vérifié — mais rend un
`<a>` sans `href`, qui n'est pas un lien : ni focalisable, ni atteignable au
clavier.

### Ce qui n'est jamais écrasé

C'est le point important : le code amorce, il ne reprend jamais la main.

| Situation | Comportement |
| --- | --- |
| Un menu porte déjà ce nom | Réutilisé tel quel, jamais recréé ni modifié |
| L'emplacement est déjà pourvu | **Laissé intact** — le choix du contributeur prime |
| Le menu a été supprimé | **Pas ressuscité** : la version d'amorçage est déjà enregistrée |
| Le menu a été renommé ou rempli | Aucun effet, le code n'y touche pas |
| Le menu porte **déjà une entrée** | **Laissé intact**, même à l'incrément de version |

Cette dernière ligne est le garde-fou central : les entrées par défaut ne sont
posées que dans un menu **vide**. Un contributeur qui a remanié sa navigation ne
doit pas la voir se dédoubler au prochain amorçage. Les brouillons comptent — une
entrée dépubliée reste le travail de quelqu'un.

Vérifié dans les deux sens : une navigation remaniée survit à un réamorçage
forcé, et un menu vidé ou détaché de son emplacement est bien réparé par
l'amorçage.

Seuls **les réseaux sociaux** restent vides : aucune maquette ne les dessine, et
y mettre des entrées inventées ferait apparaître en production une navigation
que personne n'a validée. Le pied de page, lui, a été relevé le 05/09/2026 et
ses deux menus sont amorcés.

## Ajouter un emplacement

1. Ajouter un cas à `LcdsMenuLocation` et ses bras dans `label()` **et**
   `items()` :
   ```php
   case Practitioners = 'practitioners-menu';
   // dans label() : self::Practitioners => __('Menu praticiens', 'lcds'),
   // dans items() : self::Practitioners => [],
   ```
   > Oublier l'un des deux lève `UnhandledMatchError` : un emplacement a donc
   > toujours un nom, et son contenu par défaut est toujours une décision
   > explicite — `[]` compris.
2. **Incrémenter `LCDS_MENUS_SEED_VERSION`** dans `inc/menus.php`. Sans ça, les
   environnements déjà amorcés ne créeront pas le nouveau menu — leur option est
   à jour et le code s'arrête avant.
3. L'afficher dans un gabarit :
   ```php
   wp_nav_menu([
       'theme_location' => LcdsMenuLocation::Practitioners->value,
       'container' => 'nav',
       'fallback_cb' => false,
   ]);
   ```

L'enregistrement (`register_nav_menus`) est dérivé de l'enum : il n'y a rien à
ajouter ailleurs.

> `container => 'nav'` plutôt qu'un `<nav>` écrit à la main. Pour se passer de
> conteneur, utiliser `container => ''` et **non** `false` : les deux sont falsy
> pour WordPress, mais le stub utilisé par PHPStan type l'option en `string` et
> refuse `false`. `fallback_cb => false` évite d'afficher la liste des pages
> quand aucun menu n'est assigné.

> Les tableaux d'arguments vivent dans `inc/navigation.php`, jamais dans un
> gabarit : Pint désaligne un tableau multi-lignes noyé dans du balisage
> (`statement_indentation`).

## Affichage actuel

| Emplacement | Rendu |
| --- | --- |
| `header-menu` | Navigation de l'en-tête, `depth: 1` |
| `header-cta-menu` | Bouton « Prendre RDV » de l'en-tête |
| `footer-menu` | Navigation du pied de page, `depth: 1` |
| `legal-menu` | Liens légaux du pied de page |
| `social-menu` | Pas encore affiché |

### Pourquoi le bouton d'action a son propre emplacement

Le CTA de l'en-tête est un **emplacement de menu à part**, pas un item du menu
principal ni un champ ACF.

- **Pas dans le menu principal** : sa mise en forme (pastille orange) n'a aucun
  sens au milieu des liens. Un emplacement séparé rend le glisser-déposer
  impossible.
- **Pas en champ ACF** : l'en-tête est une pièce de structure. Le lier à un
  plugin sous licence, c'est risquer une page blanche si le plugin est absent ou
  désactivé.
- **En emplacement de menu** : réutilise l'amorçage existant, l'échappement et
  les classes d'état courant restent à la charge de WordPress, et le
  contributeur l'édite là où il édite déjà la navigation.

### `depth: 1` sur la navigation

La maquette ne prévoit aucun déroulant. Un second niveau ajouté en
administration **ne serait pas rendu** — c'est délibéré, pas un oubli. Le jour
où un déroulant est maquetté, il faudra lever cette limite *et* écrire le CSS
qui va avec.
