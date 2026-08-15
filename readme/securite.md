# Sécurité

Le socle sécurité repose sur quatre couches : la **configuration** (constantes de
durcissement), le **serveur web** (en-têtes HTTP et CSP), le **thème** (réduction
de la surface exposée) et la **CI** (CVE + secrets).

## 1. Configuration — `config/application.php`

| Constante                    | Valeur  | Rôle |
| ---------------------------- | ------- | ---- |
| `DISALLOW_FILE_EDIT`         | `true`  | Supprime l'éditeur de code de l'admin. |
| `DISALLOW_FILE_MODS`         | `true`  | Interdit l'installation/mise à jour de plugins et thèmes depuis l'admin (désactivé en `development`). |
| `AUTOMATIC_UPDATER_DISABLED` | `true`  | Les mises à jour passent par Composer, pas par WordPress. |
| `WP_DEBUG_DISPLAY`           | `false` | Aucune trace d'erreur renvoyée au navigateur. |
| `DISALLOW_INDEXING`          | `true` hors prod | Bloque l'indexation de la préprod (`roots/bedrock-disallow-indexing`). |

> **Pourquoi interdire les écritures depuis l'admin ?** L'admin n'est pas un
> canal de déploiement. Un compte administrateur compromis ne doit pas pouvoir
> écrire du PHP sur le serveur — c'est ce qui transforme un vol de mot de passe
> en exécution de code distant.

Les **sels d'authentification** viennent du `.env` et sont **uniques par
environnement**. Ils ne sont jamais versionnés.

## 2. Serveur web — `website/.htaccess`

Tout est dans le bloc `# BEGIN LCDS Security`, **au-dessus** des blocs générés
par WordPress et Converter for Media (qui sont réécrits automatiquement : n'y
placez jamais de règle à la main).

### En-têtes posés

| En-tête | Valeur | Rôle |
| --- | --- | --- |
| `Content-Security-Policy-Report-Only` | *(voir ci-dessous)* | Anti-XSS. **Front uniquement.** |
| `X-Content-Type-Options` | `nosniff` | Empêche le MIME-sniffing des assets. |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Limite les fuites de `Referer`. |
| `Permissions-Policy` | `geolocation=(), camera=(), microphone=(), payment=(), browsing-topics=()` | Désactive des API navigateur. |
| `X-Frame-Options` | `SAMEORIGIN` | Anti-clickjacking (fallback legacy de `frame-ancestors`). |
| `Cross-Origin-Opener-Policy` | `same-origin` | Isole le contexte de navigation. |
| `Strict-Transport-Security` | *(commenté)* | **Production/HTTPS uniquement** — à décommenter une fois le site en HTTPS. |

### Fichiers bloqués

Fichiers cachés, `wp-config.php`, `composer.json/lock`, `package*.json`, `.env`,
`.log`, `.sql`, `.sh`, `.bak`, `xmlrpc.php`, et les dossiers `vendor/` /
`node_modules/` sous `app/`. Le listing de répertoire est coupé (`Options -Indexes`).

> La règle sur `vendor|node_modules` passe par `mod_rewrite` et **non** par
> `<DirectoryMatch>` : les directives `<Directory*>` sont interdites en contexte
> `.htaccess` et provoqueraient une erreur 500 au démarrage d'Apache.

### CSP : périmètre et rollout

- **Front uniquement.** L'admin et le login vivent sous `/wordpress-core/` et
  reposent sur du script inline / `eval` (Gutenberg, Customizer) : la CSP y est
  retirée par une directive `<If>`. **Ne jamais** l'appliquer à l'admin.
- **Report-Only par défaut.** Rien n'est bloqué, les violations sont signalées
  dans la console du navigateur. Pour passer en enforce :
  1. parcourir chaque template du front, console ouverte, corriger les violations ;
  2. renommer l'en-tête en `Content-Security-Policy` (sans `-Report-Only`).

CSP livrée :

```
default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';
img-src 'self' data:; font-src 'self'; connect-src 'self';
frame-ancestors 'self'; base-uri 'self'; object-src 'none'; form-action 'self'
```

- **`script-src 'self'` strict** est tenable : le thème ne charge **aucun script
  tiers**, et l'emoji du cœur (script inline) est désactivé par
  `inc/security.php`. ⚠️ Dès qu'un bloc core interactif est activé (lightbox,
  bloc navigation, bloc query), l'*Interactivity API* injecte une import map
  inline qui violera `'self'` → ajouter un nonce ou relâcher en `'unsafe-inline'`.
- **`style-src 'unsafe-inline'`** : **obligatoire et non contournable**, le cœur
  et les blocs émettent des `<style>` inline.
- **`img-src 'self' data:`** : à élargir si un CDN média ou Gravatar est ajouté.
- **Production** : ajouter `upgrade-insecure-requests`, activer HSTS, et câbler
  un endpoint `report-to` **avant** tout passage en enforce.

## 3. Thème — `inc/security.php`

| Mesure | Pourquoi |
| --- | --- |
| Emoji du cœur désactivé | Supprime un `<script>` inline : c'est ce qui permet `script-src 'self'`. Gain de perf au passage. |
| `<meta name="generator">` et `?ver=` supprimés | Ne plus annoncer la version exacte de WordPress, qui transforme toute CVE en recherche ciblée. |
| Liens RSD / WLW supprimés | Endpoints de publication legacy, inutilisés, qui ne font qu'annoncer XML-RPC. |
| XML-RPC désactivé | Porte d'entrée classique du bruteforce et de l'amplification pingback. |
| `?author=N` redirigé | Bloque l'énumération d'auteurs, qui divulgue des identifiants valides. |
| `/wp/v2/users` fermé aux anonymes | Équivalent JSON de la fuite ci-dessus. |
| Message de login générique | Le formulaire ne confirme plus quels comptes existent. |

## 4. Formulaire de contact — `inc/contacts.php`

Point le plus exposé du site : un endpoint AJAX **non authentifié**
(`wp_ajax_nopriv_`) qui envoie du mail et accepte des fichiers.

| Garde-fou | Détail |
| --- | --- |
| **Nonce obligatoire** | `check_ajax_referer()` en première instruction ; le nonce est posé par `lcds_contact_form_hidden_fields()`. |
| **Destinataire côté serveur** | Résolu depuis les réglages ACF de la page (`page_id`), **jamais** lu dans la requête — sinon l'endpoint devient un relais de mail ouvert. |
| **Sanitisation** | Toute entrée passe par `sanitize_text_field()` / `sanitize_textarea_field()` ; l'adresse de réponse par `is_email()`. |
| **Échappement** | Le corps du mail est construit avec `esc_html()` : son contenu est fourni par l'attaquant. |
| **Uploads** | Allow-list extension **+** type MIME réel (`wp_check_filetype_and_ext()`), 5 Mo et 5 fichiers maximum, `is_uploaded_file()` vérifié. |
| **Pièces jointes en `tmp`** | Attachées directement depuis le répertoire temporaire de PHP. Les écrire dans `uploads/` — même une fraction de seconde — les publierait sur le web. |

Le transport passe par **`wp_mail()` + le hook `phpmailer_init`**, jamais par une
instance PHPMailer tirée de Composer : WordPress embarque sa propre copie de
`PHPMailer\PHPMailer\PHPMailer` et la charge par un `require_once` nu — deux
copies dans la même requête = erreur fatale de redéclaration.

## 5. CI

- **CVE des dépendances** : `composer audit --locked` à chaque push.
- **Détection de secrets** : gitleaks sur l'historique complet.
- **SAST** : CodeQL — **JavaScript uniquement**, CodeQL ne supporte pas PHP.
  La couverture PHP repose sur PHPStan.

Voir [`ci-cd.md`](ci-cd.md).
