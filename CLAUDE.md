# CLAUDE.md — Règles du projet (LCDS)

> **Instructions pour tout assistant Claude travaillant sur ce dépôt.**
> En cas de doute : **demander, ne pas improviser.**

---

## 1. Méthode de travail

- **Avancer par validation** : proposer et expliquer les choix, valider **avant**
  d'écrire du code pour toute tâche non triviale.
- **Challenger la demande** : proposer une alternative argumentée quand la
  consigne semble sous-optimale. L'utilisateur tranche.
- **Simplicité d'abord (KISS)** : la solution la plus simple qui fonctionne.
- **Minimiser les sorties** : réponses concises, pas de remplissage.

---

## 2. Qualité de code

- **`composer check` DOIT être au vert avant tout commit PHP.** C'est le gate du
  hook de pré-commit et de la CI — voir [`readme/qualite-code.md`](readme/qualite-code.md).
- **Types natifs obligatoires** sur tout paramètre, retour et propriété. Le type
  `array` nu suffit, les génériques `array<…>` ne sont pas exigés.
- **Style PER** (Pint) : accolade d'ouverture de fonction à la ligne suivante,
  `:` du type de retour collé à la `)`.
- **Comparaisons : variable à gauche**, jamais de conditions Yoda
  (`$field === null`, pas `null === $field`).
- **PHPStan niveau 6.** Toute neutralisation = `ignoreErrors` **ciblé sur un
  fichier et commenté** dans `phpstan.neon`. Jamais de `@phpstan-ignore` dans le
  code, jamais de baseline globale.
- **Commentaires : le moins possible.** Le code se documente par ses noms.
  N'écrire un commentaire que si, sans lui, un dev **casserait** le code —
  typiquement un piège d'API externe non déductible à la lecture. Interdits :
  reformuler un nom de fonction, décrire la ligne suivante, rappeler une règle
  générale, raconter l'historique de la demande.
- **DRY** : une valeur = un seul endroit (variables SCSS côté styles, constantes
  / enums côté PHP).
- **Nommage** : pas de variables de moins de 3 caractères ; booléens préfixés
  `is` / `has` / `can`.

---

## 3. Langue

- **Code en anglais** : noms de variables, de fonctions, commentaires, docblocks.
- **Documentation en français** (README, `readme/`).
- **Chaînes affichées en français**, via `__()` / `_e()`. Le text-domain `lcds`
  DOIT rester un **littéral** (sinon `wp i18n make-pot` casse).

---

## 4. Sécurité — non négociable

- **Toute entrée est hostile.** Sanitiser en entrée (`sanitize_*`), échapper en
  sortie (`esc_html`, `esc_attr`, `esc_url`), préparer les requêtes SQL
  (`$wpdb->prepare`).
- **Tout endpoint AJAX / REST public** vérifie un **nonce** et les capabilities.
  Le point d'entrée `wp_ajax_nopriv_*` du formulaire de contact est le plus
  exposé du site : voir [`readme/securite.md`](readme/securite.md).
- **Jamais de destinataire de mail, de chemin de fichier ou d'identifiant de
  ressource lu directement depuis la requête** sans validation côté serveur.
- **Jamais de secret en dur** dans le code : tout passe par le `.env`.
  Un secret committé = un secret à révoquer, même après suppression du commit.
- **Uploads** : allow-list d'extensions **et** vérification du type MIME réel.
- **Ne pas modifier `website/wordpress-core/`** ni le contenu des plugins : ce
  sont des dépendances Composer, écrasées à la prochaine mise à jour.

---

## 5. Cache

- Le cache applicatif vit dans `website/app/mu-plugins/`, **pas** dans le thème.
- **Tout ce qui fait varier la sortie doit être dans la clé** (suffixe) : le menu
  d'en-tête est déjà mis en cache par variante mobile/desktop.
- TTL > 0 obligatoire ; cacher des scalaires/tableaux, jamais des `WP_Post`.
- Ajouter une clé = ajouter un `case` **et** son bras dans les deux `match` de
  `LcdsCacheKey`. Voir [`readme/cache.md`](readme/cache.md).

---

## 6. Git

- **Une branche par ticket** : `feature/xxx`. Bases : `main` (prod),
  `develop` (préprod).
- **Messages de commit** : `[{ticket}][{TYPE}] message` — `FEAT` ou `FIX`.
- **Ne jamais committer ni pousser sans demande explicite de l'utilisateur.**
- **Ne jamais committer le `.env`** ni un fichier contenant un secret.

---

## 7. Documentation

Tenir à jour `readme/` quand une décision de socle change (sécurité, cache, CI,
qualité). Une règle non documentée sera contournée au prochain sprint.
