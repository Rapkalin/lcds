# Cache

Trois briques distinctes, à ne pas confondre :

| Brique | Rôle | Où |
| --- | --- | --- |
| **Cache applicatif** | Mémorise des données coûteuses à recalculer (requêtes lourdes, appels API). | module `rapkalin-cache` (paquet Composer) |
| **Cache pleine page** | Sert le HTML complet d'une page depuis un fichier statique, sans relancer WordPress. | WP Super Cache (désactivé par défaut) |
| **OPcache** | Cache du bytecode PHP. | Réglage serveur (production) |

---

## 1. Cache applicatif

Il vient du module maison **`rapkalin-cache`**, paquet Composer privé
(<https://github.com/Rapkalin/rapkalin-cache>) installé dans
`website/app/mu-plugins/rapkalin-cache/` — voir [`deploiement.md`](deploiement.md).
Son API, son invalidation et ses conventions sont dans son `README.md` ; ce
fichier ne décrit que ce qui est propre à LCDS.

> Jusqu'à la 3.4.1, LCDS avait sa propre copie (`lcds-cache.php`, enums
> `LcdsCacheKey`/`LcdsCacheGroup`), **jamais appelée par le thème**. Le module en
> est la version aboutie : elle a été retirée en 4.0.0.

**État : aucune clé déclarée.** Le module tourne à vide. Ce qu'il fait déjà :

- le bouton **Vider le cache** dans la barre d'outils, pour les comptes qui
  ont `edit_others_posts` — administrateurs **et contributeurs LCDS** ;
- `wp rapkalin-cache flush` (lancé par le déploiement) et `wp rapkalin-cache
  status` ;
- la purge du cache pleine page sur ce que WP Super Cache ne surveille pas
  (options ACF, titre du site, termes), le jour où celui-ci est activé.

### Déclarer la première clé

Une enum **du thème** qui implémente `RapkalinCacheEntry`, enregistrée par le
filtre `rapkalin_cache_entries`, puis `rapkalin_cache_remember()` — exemple
complet dans le `README.md` du module.

> ⚠️ **Tout ce qui fait varier la sortie doit être dans la clé** (son
> `$suffix`). Langue, rôle, variante mobile/desktop, identifiant de contenu : ce
> qui n'est pas dans la clé sera servi à tout le monde à partir du premier
> visiteur.

Conventions du module, à tenir : **TTL > 0**, des **scalaires et tableaux**
(jamais `WP_Post` ni `WP_Query`), jamais `posts_per_page => -1`, un callback qui
peut toujours recalculer la valeur.

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
- ⚠️ **Variantes.** Si le front finit par servir un rendu différent selon
  l'appareil ou la langue, le cache pleine page **doit** être configuré en
  conséquence (mode « mobile support », clés par langue), sinon la première
  version générée est renvoyée à tout le monde.

**Purge** après un déploiement ou une grosse mise à jour de contenu (le
déploiement la lance lui-même) — cache applicatif et pages ensemble :

```bash
docker compose exec php wp rapkalin-cache flush --allow-root
```

---

## 3. Maintenance

**Purge des transients expirés** — automatique via WP-Cron (2×/jour). ⚠️ En
production, WP-Cron est désactivé (`DISABLE_WP_CRON=true`) au profit d'un cron
système : sans ce cron, la purge ne tourne plus et `wp_options` gonfle.

**OPcache** — après tout déploiement, **recharger PHP-FPM** pour purger le
bytecode compilé, sinon l'ancien code continue de tourner.
