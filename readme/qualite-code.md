# Qualité du code PHP

Trois outils garantissent la qualité d'écriture du PHP du projet (thème +
mu-plugins). Ils sont enchaînés par `composer check`, exécutés à chaque commit
par `.githooks/pre-commit`, et rejoués en CI.

> Toutes les commandes ci-dessous se lancent dans le conteneur :
> `docker compose exec php composer <script>`, ou via les alias `dcheck` /
> `dtest` après `source aliases.sh`. Le hook de pré-commit choisit
> automatiquement le conteneur quand il tourne, l'hôte sinon.

| Outil | Rôle | Config |
| --- | --- | --- |
| **Laravel Pint** | Style / formatage (auto-corrige) | `pint.json` |
| **PHPCS + Slevomat** | Déclaration **native** des types (param / retour / propriété) | `phpcs.xml` |
| **PHPStan** | Analyse statique (bugs, cohérence des types) | `phpstan.neon` |
| **Pest** | Tests unitaires (hors WordPress) | `phpunit.xml.dist` |

## 1. Style (Pint, preset PER)

Le style suit le **standard PER** sans écart. En particulier :

- **Accolade d'ouverture de fonction/méthode à la ligne suivante.**
- **`:` de type de retour collé à la `)`**, espace uniquement après (`): array`).
- **Pas de conditions Yoda** : la variable à gauche (`$field === null`, et non
  `null === $field`). Une condition se lit « ce que je teste, puis ce à quoi je
  le compare ».

```bash
composer lint       # vérifie sans modifier
composer lint:fix   # corrige automatiquement
```

> Pint est toujours lancé en **scan complet**, jamais avec une liste de fichiers :
> avec des chemins explicites il ignore le `exclude` de `pint.json` et
> analyserait le cœur WordPress et `node_modules`.

## 2. Déclaration des types (PHPCS + Slevomat)

Tout paramètre, tout retour et toute propriété **doit déclarer un type natif**.
Seuls les sniffs `TypeHints` de Slevomat sont activés (le style reste à Pint).

- Les génériques `array<…>` **ne sont pas exigés** : le type `array` nu suffit.
- `composer types:fix` ajoute automatiquement les types déductibles. Les
  paramètres sans aucune information de type sont à compléter **à la main**.

```bash
composer types      # vérifie
composer types:fix  # ajoute ce qui est déductible
```

### Exception : callbacks surchargeant le cœur WordPress

On **ne peut pas** typer un paramètre qui surcharge une méthode WP dont le parent
n'est pas typé (ex. `\Walker_Nav_Menu::start_el`) : PHP refuse, même avec `mixed`.
Ne typer alors **que le retour** et neutraliser le sniff localement :

```php
/**
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
public function start_el(&$output, $data_object, $depth = 0, $args = null, $id = 0): void
```

## 3. Analyse statique (PHPStan niveau 6)

Le niveau 6 signale les types manquants inférables et les incohérences réelles.
Les fonctions WordPress / ACF / Yoast sont connues via des packages de *stubs*.

Toute neutralisation d'erreur doit être un `ignoreErrors` **ciblé sur un
fichier et commenté** dans `phpstan.neon` — jamais un `@phpstan-ignore` semé dans
le code, jamais une baseline globale. Les deux exceptions actuelles sont des
limitations de stub documentées (`wp_nav_menu` avec `container => false`, et les
propriétés que `wp_setup_nav_menu_item()` greffe à l'exécution sur `WP_Post`).

```bash
composer stan
```

## 4. Tests (Pest)

La suite tourne **hors WordPress** : pas de base, pas de `wp-load`. Les constantes
nécessaires (`ABSPATH`, constantes de temps) sont stubbées dans `tests/Pest.php`.

Elle couvre les invariants qu'on ne veut pas découvrir en production — par
exemple le contrat « une clé de cache a toujours un TTL et un groupe ».

```bash
composer test
```

> Pest déduit sa racine du dossier vendor (`website/vendor`) : le script passe
> `--test-directory=../tests` pour le ramener sur `tests/` à la racine du dépôt.

## 5. Tout enchaîner

```bash
composer check      # lint + types + stan
```

**C'est le gate du hook pré-commit et de la CI : il DOIT être au vert avant de
committer du PHP.**

Activation du hook, une fois par clone :

```bash
composer setup-hooks
```

Échappatoire ponctuelle : `git commit --no-verify`.
