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

> **Pourquoi le `.htaccess` et non le vhost du conteneur ?** Parce que le
> `.htaccess` part avec le code en production, alors que
> `docker/apache/000-default.conf` ne sert qu'au poste de développement. Une
> seule source pour la CSP, appliquée partout. Le vhost se contente de poser
> `AllowOverride All` — **sans lui, tout ce bloc est ignoré** et le site part
> sans en-têtes de sécurité.

> ⚠️ **En production et en préprod, c'est `shared/.htaccess` qui est servi**, pas
> le fichier versionné : il est lié au déploiement pour que chaque environnement
> puisse ajouter ses directives (mot de passe sur la préprod). Le fichier du
> dépôt reste la **référence** — il amorce `shared/` sur un nouvel environnement,
> et toute divergence est signalée dans le log de déploiement. Conséquence :
> **une modification des en-têtes ici doit être reportée à la main** sur les
> serveurs déjà en place. Voir [`deploiement.md`](deploiement.md).

> ⚠️ **Les deux `.htaccess` sont réécrits par des plugins.** *Converter for
> Media* régénère ses blocs dans `website/.htaccess` **et**
> `website/app/.htaccess` dès qu'on enregistre ses réglages, parfois avec des
> **chemins absolus propres à la machine** (`/var/www/html/...` dans le
> conteneur), qui seraient faux en production. `wp rewrite flush --hard` réécrit
> aussi le bloc WordPress.
> **Réflexe : après toute manipulation de ces plugins, relire `git diff` sur les
> deux fichiers avant de committer**, et rejeter tout chemin absolu. Le bloc
> `# BEGIN LCDS Security` est placé **au-dessus** des marqueurs générés
> précisément pour survivre à ces réécritures.

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
frame-src https://www.youtube-nocookie.com;
frame-ancestors 'self'; base-uri 'self'; object-src 'none'; form-action 'self'
```

- **`frame-src`** n'ouvre que le lecteur YouTube du hero, et seulement sur le
  domaine `youtube-nocookie.com`. Ce domaine **ne dispense pas de
  consentement** : il ne dépose pas de cookie avant la lecture, mais écrit des
  identifiants dans le stockage local et transmet l'adresse IP du visiteur à
  Google. L'iframe n'est donc créée qu'après l'accord du visiteur, recueilli
  par tarteaucitron.js (voir plus bas).

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
copies dans la même requête = erreur fatale de redéclaration. Il est configuré
par le mu-plugin `rapkalin-mail` (Réglages → Email), dont le mot de passe SMTP
est chiffré en base — voir [`email.md`](email.md).

## 5. CI

- **CVE des dépendances** : `composer audit --locked` à chaque push.
- **Détection de secrets** : gitleaks sur l'historique complet.
- **SAST** : CodeQL — **JavaScript uniquement**, CodeQL ne supporte pas PHP.
  La couverture PHP repose sur PHPStan.

Voir [`ci-cd.md`](ci-cd.md).

## Consentement aux cookies

Le site ne dépose **aucun traceur** tant qu'aucun service tiers n'est intégré.
Le seul aujourd'hui est la vidéo YouTube du hero, que le contributeur peut
choisir à la place d'un fichier.

- **Outil : tarteaucitron.js** (licence MIT), dépendance npm du thème. Le build
  recopie ses fichiers minifiés dans `dist/tarteaucitron/` : il charge sa
  langue, ses services et sa feuille depuis le dossier de son propre script, et
  `node_modules` ne part pas au déploiement. Tout est servi par le site, donc
  compatible avec `script-src 'self'`. Aucun appel vers les serveurs de
  tarteaucitron : ils ne servent qu'à l'offre payante, avec une clé que le site
  n'a pas.
- **Chargé seulement là où il sert** : le hero l'appelle
  (`lcds_enqueue_consent_manager()`) quand il rend une vidéo YouTube. Les autres
  pages n'ont ni bandeau ni script.
- **Refuser est aussi simple qu'accepter** (deux boutons de même poids), le
  choix est mémorisé, et le lien « Gérer les cookies » du pied de page permet de
  le **retirer** à tout moment. Ce lien n'apparaît que sur les pages qui
  chargent le gestionnaire.
- **Ajouter un service** (mesure d'audience, carte…) = l'ajouter à la liste
  `tarteaucitron.job` dans `initConsentManager()` (`assets/scripts/app.js`),
  appeler `lcds_enqueue_consent_manager()` sur les pages concernées, et ouvrir
  la CSP au domaine du service.

