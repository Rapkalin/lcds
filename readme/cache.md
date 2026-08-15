# Cache

Trois briques distinctes, à ne pas confondre :

| Brique | Rôle | Où |
| --- | --- | --- |
| **Cache applicatif** | Mémorise des données coûteuses à recalculer (requêtes lourdes, appels API). | `website/app/mu-plugins/` |
| **Cache pleine page** | Sert le HTML complet d'une page depuis un fichier statique, sans relancer WordPress. | WP Super Cache (désactivé par défaut) |
| **OPcache** | Cache du bytecode PHP. | Réglage serveur (production) |

---

## 1. Cache applicatif

Le mu-plugin `website/app/mu-plugins/lcds-cache.php` met en cache, via les
**transients** WordPress, des données coûteuses à recalculer. C'est une **brique
de base, à faire évoluer** selon les besoins.

> Il vit dans un **mu-plugin** et non dans le thème : le cache est de
> l'infrastructure. Il doit rester disponible pour WP-CLI, le cron et l'admin, et
> survivre à un changement de thème.

### Utilisation

```php
$menu = lcds_cache_remember(LcdsCacheKey::HeaderMenu, 'build_menu', $variant);
```

Signature :

```php
lcds_cache_remember(
    LcdsCacheKey $key,
    callable $callback,
    string $suffix = ''
): mixed
```

La fonction renvoie la valeur en cache si elle existe, sinon exécute
`$callback`, la stocke, puis la renvoie. **Le TTL et le groupe sont portés par la
clé** (l'enum `LcdsCacheKey`) : l'appel ne fait que choisir l'entrée et fournir
le callback. Pour une entrée **paramétrée** (par page, par variante…), passer un
`$suffix`.

> ⚠️ **Le suffixe sert à séparer les variantes.** Le menu d'en-tête diffère entre
> mobile et desktop (`get_item_menu_children()` retire les enfants de taxonomie
> sur petit écran) : `get_header_menu()` passe donc `mobile` / `desktop` en
> suffixe. Sans ça, le premier visiteur déciderait du menu servi à tous les
> autres. **Tout ce qui fait varier la sortie doit être dans la clé.**

### Invalidation

L'invalidation se fait par **versionnement** : plutôt que de supprimer les
transients un par un, on incrémente une version. Les anciennes clés ne sont alors
**plus jamais relues** (invalidation immédiate) ; leur ligne, devenue orpheline
dans `wp_options`, est purgée plus tard par WordPress une fois son TTL dépassé.

- `lcds_cache_flush_group(LcdsCacheGroup::Content)` — invalide un groupe ;
- `lcds_cache_flush_all()` — invalide tout le cache applicatif.

Hooks d'invalidation automatique (`cache/invalidation.php`) :

| Groupe | Invalidé par |
| --- | --- |
| `Content` | publication / màj / suppression de `post` et `page` (filtrable via `lcds_cache_content_post_types`) ; création / édition / suppression de termes |
| `Menus` | mise à jour d'un menu (`wp_update_nav_menu`), **et** les mêmes événements que `Content` |
| `Default` | jamais automatiquement (seulement `flush_all()` ou le TTL) |
| *(tous)* | changement de thème (`switch_theme`) |

> `Menus` suit aussi les contenus et les termes : le menu d'en-tête est construit
> à partir du menu **et** des pages qu'il pointe (gabarit, identifiants de blocs
> ACF) et d'une hiérarchie de taxonomie. Une page modifiée peut donc changer le
> menu sans que `wp_update_nav_menu` ne soit jamais déclenché.

### Déclarer une clé

Les clés sont définies dans `enums/LcdsCacheKey.php` — **source unique de
vérité** : chaque `case` porte son nom, son TTL (`ttl()`) et son groupe
(`group()`). Pour en ajouter une :

1. Ajouter un `case` et son bras dans **chaque** `match` :
   ```php
   case HomeSlider = 'home_slider';
   // dans ttl()   : self::HomeSlider => 15 * MINUTE_IN_SECONDS,
   // dans group() : self::HomeSlider => LcdsCacheGroup::Content,
   ```
   > Oublier le `ttl()` et/ou le `group()` lève `UnhandledMatchError`
   > (fail-fast) : une clé a donc **toujours** un TTL et un groupe. La suite Pest
   > (`tests/Unit/CacheKeyTest.php`) transforme cet oubli en échec de CI.
2. L'utiliser : `lcds_cache_remember(LcdsCacheKey::HomeSlider, $callback);`

### Déclarer un groupe

1. Ajouter un cas à `enums/LcdsCacheGroup.php` :
   ```php
   case Practitioners = 'practitioners';
   ```
2. Câbler son invalidation dans `cache/invalidation.php` :
   ```php
   add_action('save_post_practitioner', function () {
       lcds_cache_flush_group(LcdsCacheGroup::Practitioners);
   });
   ```
3. Y rattacher une clé dans `LcdsCacheKey`.

### Conventions

- **TTL > 0 obligatoire** (un transient sans expiration serait chargé en autoload
  à chaque requête).
- Cacher des **scalaires / tableaux**, **pas** d'objets `WP_Post` / `WP_Query`
  (lourds à sérialiser, état potentiellement périmé).
- Le cache est **opportuniste** : la valeur peut disparaître à tout moment ; le
  callback doit toujours pouvoir la recalculer. **Jamais** d'unique source de vérité.
- **Pas de clé ré-entrante** : un callback ne doit pas re-`remember` sa propre clé.
- Bannir `posts_per_page => -1` / `numberposts => -1` dans les requêtes cachées.

---

## 2. Cache pleine page (WP Super Cache)

Le plugin est **fourni mais désactivé** (`WP_CACHE='false'`). Pour l'activer :

1. `WP_CACHE='true'` dans le `.env` (définit la constante `WP_CACHE`) ;
2. activer **WP Super Cache** dans l'admin — cela génère
   `website/app/advanced-cache.php` et `website/app/wp-cache-config.php`.
   **Committer ces deux fichiers** (les lignes correspondantes sont prêtes,
   commentées, dans `.gitignore`) : en production `DISALLOW_FILE_MODS=true`
   empêche de les régénérer ;
3. s'assurer que `website/app/cache/` est **inscriptible** par le serveur web.

### Règles de bypass — à respecter

- Visiteurs **connectés** et requêtes **POST** ne sont pas cachés (comportement
  par défaut du plugin).
- ⚠️ **Nonces.** Le formulaire de contact embarque un nonce, qui expire au bout
  de 12 à 24 h. Une page mise en cache avec un nonce périmé fait échouer toutes
  les soumissions. **Exclure `/contact` (et toute page à formulaire) via les
  « Rejected URIs » du plugin**, et garder un **TTL ≤ 12 h**.
- ⚠️ **Variantes mobiles.** Le menu d'en-tête diffère selon le user-agent. Si le
  cache pleine page est activé, il **doit** être configuré en mode « mobile
  support », sinon la première version servie (mobile ou desktop) sera renvoyée à
  tout le monde.

**Purge** après un déploiement ou une grosse mise à jour de contenu :

```bash
docker compose exec php wp eval 'wp_cache_clear_cache();' --allow-root
```

---

## 3. Maintenance

**Purge des transients expirés** — automatique via WP-Cron (2×/jour). ⚠️ En
production, WP-Cron est désactivé (`DISABLE_WP_CRON=true`) au profit d'un cron
système : sans ce cron, la purge ne tourne plus et `wp_options` gonfle.

**OPcache** — après tout déploiement, **recharger PHP-FPM** pour purger le
bytecode compilé, sinon l'ancien code continue de tourner.
