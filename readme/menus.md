# Menus de navigation

Les emplacements de menu et leur création vivent dans le thème :

| Fichier | Rôle |
| --- | --- |
| `inc/enums/LcdsMenuLocation.php` | **Source unique de vérité** : un cas = un emplacement |
| `inc/menus.php` | Enregistrement des emplacements + création automatique |

## Création automatique

Un contributeur ne doit jamais tomber sur l'écran « Créez votre premier menu ».
À la première requête d'admin d'un environnement, chaque emplacement déclaré
reçoit un menu **vide**, rattaché automatiquement.

Le mécanisme est branché sur `admin_init` et gardé par une option
(`lcds_menus_seed_version`) : passé le premier passage, il se réduit à une
lecture d'option. **Le front n'est jamais ralenti.**

### Ce qui n'est jamais écrasé

C'est le point important : le code amorce, il ne reprend jamais la main.

| Situation | Comportement |
| --- | --- |
| Un menu porte déjà ce nom | Réutilisé tel quel, jamais recréé ni modifié |
| L'emplacement est déjà pourvu | **Laissé intact** — le choix du contributeur prime |
| Le menu a été supprimé | **Pas ressuscité** : la version d'amorçage est déjà enregistrée |
| Le menu a été renommé ou rempli | Aucun effet, le code n'y touche pas |

Les menus sont créés **vides** : les remplir est un travail éditorial.

## Ajouter un emplacement

1. Ajouter un cas à `LcdsMenuLocation` et son bras dans `label()` :
   ```php
   case Practitioners = 'practitioners-menu';
   // dans label() : self::Practitioners => __('Menu praticiens', 'lcds'),
   ```
   > Oublier le libellé lève `UnhandledMatchError` : un emplacement a donc
   > toujours un nom.
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

> `container => 'nav'` plutôt qu'un `<nav>` écrit à la main : `container => false`
> est refusé par le stub WordPress utilisé par PHPStan. `fallback_cb => false`
> évite d'afficher la liste des pages quand aucun menu n'est assigné.

## Affichage actuel

**Aucun menu n'est rendu** pour l'instant : `header.php` et `footer.php` sont
réduits au nom du site et à la ligne de copyright, le temps que l'arborescence
soit arrêtée. Les emplacements existent, les menus sont créés et administrables :
il ne reste qu'à les afficher.
