# CLAUDE.md — Règles du projet (LCDS)

> **Instructions pour tout assistant Claude travaillant sur ce dépôt.**
> En cas de doute : **demander, ne pas improviser.**

---

## 1. Méthode de travail

- **Avancer par validation** : proposer et expliquer les choix, valider **avant**
  d'écrire du code pour toute tâche non triviale.
- **Simplicité d'abord (KISS)** : la solution la plus simple qui fonctionne.
- **Minimiser les sorties** : réponses concises, pas de remplissage.

### Challenger la demande — passe obligatoire avant de répondre

Avant toute réponse impliquant une décision technique, se poser ces questions et
**dire ce qui coince** :

1. **Est-ce que ça résout le vrai problème ?** La demande décrit parfois une
   solution ; vérifier qu'elle traite bien la cause.
2. **Est-ce que ça tient dans 6 mois ?** Duplication, valeur en dur, couplage,
   configuration qui devra être maintenue à deux endroits.
3. **Qu'est-ce que ça casse ?** Sécurité, données, compatibilité, environnements
   autres que celui sous les yeux.
4. **Y a-t-il plus simple ?** Si oui, le proposer — même si la demande est claire.
5. **Sur quoi je m'avance ?** Version d'un paquet, comportement d'une API,
   existence d'une option : **vérifier plutôt qu'affirmer**.

Règles de conduite :

- **Signaler ≠ bloquer.** Exposer la réserve en une ou deux phrases, puis
  **faire le travail demandé**. L'utilisateur tranche.
- **Une demande reformulée ou répétée vaut décision** : appliquer sans réouvrir
  le débat.
- **Ne pas fabriquer d'objection.** Quand la demande est bonne, le dire en une
  ligne et exécuter. Un challenge systématique et creux ne vaut rien.
- **Distinguer le fait de l'avis** : « ça ne marchera pas parce que X » (vérifié)
  n'est pas « je préférerais Y » (préférence).

---

## 2. Qualité de code

- **Le projet tourne dans Docker.** Toute commande PHP / Composer / WP-CLI passe
  par `docker compose exec php …` — voir [`readme/docker.md`](readme/docker.md).
  Ne jamais supposer que PHP est disponible sur l'hôte.
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
- **Avant de committer un `.htaccess`**, relire le `git diff` : les plugins les
  régénèrent et y injectent parfois des chemins absolus propres à la machine,
  faux dans tout autre environnement. Voir [`readme/securite.md`](readme/securite.md).
- **Jamais de secret ni de plugin sous licence dans le dépôt** : ils vivent dans
  `shared/`, qui n'est ni versionné ni écrasé par un déploiement.

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
