# Rôles et périmètre de l'administration

## Le rôle « Contributeur LCDS »

`lcds_contributeur`, bâti sur les capacités de l'**éditeur** — qui n'a ni
`manage_options`, ni `activate_plugins`, ni `switch_themes` — plus deux ajouts :

| Capacité | Ce qu'elle ouvre |
| --- | --- |
| `edit_theme_options` | les menus et le personnalisateur |
| `lcds_manage_settings` | l'écran *Réglages → Configuration*, et lui seul |

Ce que voit un contributeur, vérifié sur une session authentifiée :

```
Tableau de bord | Pages | Médias | Apparence | Réglages | Profil
    Pages     : Toutes les pages · Ajouter une page
    Médias    : Library · Ajouter un fichier média
    Apparence : Personnaliser · Menus
    Réglages  : Configuration
```

## Pourquoi une capacité dédiée plutôt que `manage_options`

`manage_options` n'ouvre pas « Général » et « Écriture » : il ouvre **les sept
écrans de Réglages du cœur** — dont *Lecture*, qui désigne la page d'accueil, et
*Permaliens* — et `options.php`, l'**éditeur brut de toutes les options en
base**. Vérifié dans `wp-admin/menu.php` et `wp-admin/options.php`.

Le donner à un contributeur revient à en faire un administrateur. La demande
initiale portait sur Général et Écriture ; ils ne sont **pas** exposés, et c'est
un arbitrage à trancher — voir « Ce qui reste ouvert ».

WordPress promeut le premier sous-menu accessible au rang de parent quand le
parent ne l'est pas (`wp-admin/includes/menu.php`). « Réglages » apparaît donc
avec « Configuration » pour seul contenu, sans qu'aucune capacité du cœur soit
accordée.

## Masquer ne protège pas

Retirer une entrée de menu est **cosmétique** : l'URL reste tapable. Chaque
écran hors périmètre est donc aussi refusé à l'accès, par une **allow-list** —
`inc/enums/LcdsAdminScreen.php`.

Une allow-list et non une liste d'interdits : un écran ajouté par une future
extension est refusé par défaut. Une liste noire l'aurait laissé passer sans que
personne ne s'en aperçoive.

`isAllowed()` est une fonction **pure** — elle ne lit ni l'utilisateur courant
ni la base — ce qui la rend vérifiable par `tests/Unit/AdminScreenTest.php`, là
où le garde-fou qui l'appelle a besoin d'une vraie requête d'administration.

Mesuré sur une session de contributeur :

| Écran | Réponse |
| --- | --- |
| Tableau de bord, Pages, Médias, Profil, Menus, Configuration | **200** |
| Articles, Thèmes, Éditeur de site, Compositions, Réglages du cœur, `options.php`, Outils, Extensions, Utilisateurs, Commentaires, Éditeur de fichiers, Yoast | **403** |

> **Deux trous que seul un vrai coup d'œil a révélés** : « Compositions »
> (l'éditeur de site avec un paramètre, donc un autre slug) et **tout le menu de
> Yoast** restaient affichés. Le menu de Yoast s'enregistre sous
> `wpseo_page_academy` — un slug qui porte son compteur de notifications et
> change d'une version à l'autre — il est donc retiré par **préfixe**.

## Deux rôles attribuables, sur huit déclarés

`editable_roles` ne laisse que **Administrateur** et **Contributeur LCDS**. Les
six autres — les rôles par défaut de WordPress et les deux qu'ajoute Yoast —
restent **déclarés** : les retirer casserait un compte qui les porte. Ils ne
sont simplement plus proposés.

Une exception, et elle est nécessaire : **le rôle du compte en cours d'édition
reste dans la liste**, même hors des deux. Sans son option, le navigateur en
sélectionne une autre et l'enregistrement change le rôle sans que personne l'ait
demandé. Vérifié par la campagne, sur un compte témoin créé puis supprimé.

## « Mon compte », réduit à l'utile

Un contributeur ne voit plus que : couleurs de l'interface, barre d'outils,
identifiant, prénom, nom, pseudo, adresse e-mail, et la section **Gestion du
compte** (mot de passe et sessions).

L'écran n'est réduit que sur **son propre profil** : un administrateur qui
modifie un contributeur garde le formulaire entier.

Trois sections partent **côté serveur**, par de vrais filtres — elles ne sont
pas rendues du tout :

| Section | Filtre |
| --- | --- |
| Capacités supplémentaires | `additional_capabilities_display` |
| Mots de passe d'application | `wp_is_application_passwords_available_for_user` |
| Moyens de contact | `user_contactmethods` |

S'y ajoute le retrait des sections posées par les extensions — Yoast en met une
sur chaque profil — par `remove_all_actions()` sur les trois accroches de
l'écran.

### Le reste part par une feuille de style, et c'est assumé

Le cœur **n'expose aucun filtre par champ** : la seule prise est la classe qu'il
pose sur chaque ligne. Les sept lignes hors périmètre sont donc masquées en CSS.

C'est de la **mise en forme, pas une barrière** — un champ masqué reste
soumettable. Ça n'ouvre rien : ce sont les données du compte lui-même, qu'un
contributeur a le droit de modifier. Ce qui protège est ailleurs, et c'est
l'objet de la section « Masquer ne protège pas » ci-dessus.

Deux points de vigilance, tous deux vérifiés par la campagne :

- **Les sélecteurs `:has()` vivent dans leur propre règle.** Un navigateur qui
  les ignore jette la **liste entière** de sélecteurs — les lignes ordinaires
  seraient tombées avec eux. Ils servent à désigner la section « À propos de
  vous », que le cœur ne regroupe dans aucun conteneur.
- **Toute ligne du cœur est explicitement classée** — gardée, masquée, ou
  traitée ailleurs. Une mise à jour de WordPress qui ajoute un champ fait
  **échouer la suite de tests** plutôt que de le laisser surgir. C'est
  `LcdsProfileField`, et c'est l'assertion qui porte le tout.

## Le tableau de bord

Réduit à **« D'un coup d'œil »**. Les autres blocs parlent de WordPress et non
du site : activité de publication, brouillon rapide, actualités du projet, santé
du site. Le nettoyage s'applique à tout le monde, administrateurs compris ;
le restreindre au seul rôle de contribution tient en une ligne.

## La boîte SEO passe sous la contribution

Yoast s'enregistre en priorité `high` : sa boîte passait **avant** le champ de
contenu flexible, et un contributeur tombait sur le référencement avant d'avoir
vu la page. Le filtre `wpseo_metabox_prio` la range en `low`, donc après toutes
les boîtes de priorité normale.

Vérifié sur l'écran d'édition de la page d'accueil : `acf-group_lcds_homepage`
puis `wpseo_meta`.

## Faire évoluer le rôle

Les capacités sont posées une fois, gardées par `LCDS_ROLE_VERSION` : un rôle
existant n'est pas réécrit à chaque requête. **Incrémenter la constante** pour
rejouer la définition sur tous les environnements après un changement.

## Ce qui reste ouvert

- **Réglages → Général et Écriture** n'ont pas été exposés : il n'existe aucun
  moyen de les ouvrir sans donner `manage_options`. Si le client y tient, la
  bonne réponse est de **recopier les deux ou trois réglages utiles** (titre du
  site, slogan) dans l'écran *Configuration*, pas d'élargir la capacité.
- **Aucun test de bout en bout automatisé** : la vérification par session
  authentifiée a été menée à la main, elle n'est pas rejouée par la campagne.
  Celle-ci se limite aux capacités du rôle et à la priorité de la boîte SEO.
