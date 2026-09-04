# Images (WebP)

## Texte alternatif

**Il vit dans la médiathèque, et nulle part ailleurs.** `lcds_render_image()` ne
passe pas d'`alt` à `wp_get_attachment_image()`, qui va donc lire
`_wp_attachment_image_alt` sur l'attachement. Renseigner le champ « Texte
alternatif » d'un média suffit : toutes ses apparitions dans le site en héritent.

> **Ne jamais repasser `'alt' => ''` depuis un composant.** C'était le cas des
> quatre appelants, et ça rendait **les 16 images de la page d'accueil
> décoratives d'office** — un contributeur n'avait aucun moyen de décrire une
> image. Une assertion de `bin/qa-front.sh` fait maintenant échouer la campagne
> si un composant le refait.

Une image dont l'alternative n'est pas saisie sort en `alt=""`, donc décorative.
C'est le comportement voulu : la décision appartient au contributeur, pas au
gabarit. Le hero et les visuels d'étape sont dans ce cas par défaut.

Le site sert ses images en **WebP** par deux briques complémentaires, reprises du
socle Steamulo (game-france).

## 1. Conversion — mu-plugin `lcds-webp`

À chaque téléversement d'une image **JPEG ou PNG**, WordPress génère ses
sous-tailles **en WebP**. Les **SVG et GIF ne sont jamais convertis** : le
vectoriel et l'animation ne se prêtent pas à ce traitement.

C'est de l'infrastructure : le code vit dans `website/app/mu-plugins/webp/`,
toujours chargé et indépendant du thème.

| Fichier | Rôle |
| --- | --- |
| `mu-plugins/lcds-webp.php` | Index : requiert les parties du module |
| `mu-plugins/webp/config.php` | `LCDS_WEBP_QUALITY` (82), `LCDS_WEBP_SOURCE_FORMATS` |
| `mu-plugins/webp/conversion.php` | Les deux filtres WordPress |

S'appuie sur le pipeline natif de WordPress — filtre `image_editor_output_format`
depuis la 5.8 — et sur une bibliothèque GD compilée **avec le support WebP**,
c'est le cas de l'image Docker du projet. **Aucun plugin, aucun service externe.**

> Ajouter une clé de réglage = la déclarer dans `config.php`, jamais en dur dans
> `conversion.php`.

### Ce qui existe réellement sur le disque

Mesuré sur un JPEG de test de 1600×1200 (108 Ko) :

| Fichier | Poids |
| --- | --- |
| `lcds-probe.jpg` (téléversé) | 108 Ko |
| `lcds-probe.webp` (pleine taille) | 14 Ko |
| `lcds-probe-1536x1152.webp` | 15 Ko |
| `lcds-probe-1024x768.webp` | 7 Ko |
| `lcds-probe-768x576.webp` | 4 Ko |
| `lcds-probe-300x225.webp` | 1 Ko |
| `lcds-probe-150x150.webp` | 0,6 Ko |

Point à connaître : **`_wp_attached_file` pointe sur le `.webp`**, pas sur le
fichier téléversé. Le JPEG n'est pas pour autant orphelin — WordPress le suit
via `wp_get_original_image_path()`, exactement comme pour les images `-scaled`,
et le supprime avec l'attachement. Vérifié : sept fichiers avant suppression,
zéro après.

## 2. Affichage — helper `lcds_render_image()`

Défini dans `inc/images.php`, c'est le **point d'entrée unique** pour afficher
une image de contenu :

```php
echo lcds_render_image($image, ['class' => 'ma-classe', 'alt' => $alt]);
```

- **Identifiant d'attachement** ou **tableau image ACF** → sortie
  `wp_get_attachment_image()` : `srcset` responsive servi en WebP,
  `width`/`height`, `loading="lazy"`, `decoding="async"` et texte alternatif de
  la médiathèque.
- **Chaîne URL** (placeholder, ressource externe) → `<img>` simple : il n'y a
  rien à convertir.

**Règle : pour bénéficier du WebP, une image doit venir de la médiathèque** —
champ ACF image ou identifiant, jamais une URL en dur.

Pas de repli `<picture>`/JPEG : le WebP est pris en charge par tous les
navigateurs actuels.

## Médias déjà en base

Les images téléversées **avant** l'activation du module n'ont pas de sous-tailles
WebP. Pour les régénérer :

```bash
dwp media regenerate --yes
```

## Pourquoi pas de plugin

Le projet embarquait `webp-converter-for-media`, qui poursuivait le même objectif
par un mécanisme opposé : des copies dans `app/uploads-webpc/` servies par des
réécritures `.htaccess`, au lieu d'une conversion à la génération. Il a été
retiré le 01/09/2026 sur ce constat :

- **actif depuis six mois, jamais configuré** (aucune option `webpc_settings`) et
  **zéro média converti** — le seul fichier produit était son icône d'autotest ;
- son bloc dans le `website/.htaccess` versionné posait un
  `Header always set Cache-Control "private"` qui **doublait** le
  `Cache-Control: public` du bloc voisin. Mesuré : deux en-têtes contradictoires
  sur chaque image, sur toutes les requêtes.

### Et l'AVIF ?

Rien n'a été perdu en retirant le plugin. **WordPress sait produire de l'AVIF
depuis la 6.5**, par le même filtre `image_editor_output_format` : passer à
l'AVIF, ce serait une seule valeur à changer dans `config.php`, puis
`wp media regenerate`. Ce n'est donc pas une question d'architecture.

Ce qui bloque est en dessous : **l'encodage AVIF exige une bibliothèque GD
compilée avec le support AVIF**, et ce n'est le cas ni de l'image Docker du
projet, ni de l'hébergement mutualisé visé.

| | WebP | AVIF |
| --- | --- | --- |
| Conteneur du projet (`gd_info()`) | oui | **non** |
| `wp_image_editor_supports()` | oui | **non** |
| Mutualisé OVH, PHP 8.2 et 8.5 ([fil communauté](https://community.ovhcloud.com/t/support-avif-php-gd-imageavif-disponible-sur-nouvelles-offres-mutualisees-startup-ou-pro/53092)) | oui | **non**, `imageavif()` renvoie `false` |

Le plugin n'aurait donc rien pu produire de plus : il encode par GD ou Imagick,
et aucun des deux ne sait faire d'AVIF sur cet hébergement (Imagick n'est même
pas chargé dans le conteneur, WordPress retombe sur GD).

**Recommandation : rester sur le WebP.** Le gain d'AVIF est de l'ordre de 10 à
20 % de poids, mais son encodage coûte nettement plus de temps processeur à
chaque téléversement — un mauvais compromis sur un mutualisé. À rouvrir
seulement si le poids des images devient un problème *mesuré* **et** que
l'hébergeur gagne le support AVIF.

### Quand ce plugin réécrivait-il les `.htaccess` ?

Documenté ici parce que le mécanisme a coûté du temps à diagnostiquer, et que
d'autres plugins fonctionnent pareil.

Il écrivait dans **trois répertoires** — `app/`, `app/uploads/` et
`app/uploads-webpc/` — et **jamais dans `website/.htaccess`**. Sa routine
d'écriture retirait son bloc `# BEGIN/END Converter for Media` par expression
régulière puis **préfixait** les nouvelles règles en tête de fichier.

> Corollaire : le bloc trouvé au *milieu* du `website/.htaccess` versionné n'avait
> pas été écrit par le plugin. C'était une copie manuelle, que ni la
> désactivation ni la désinstallation n'auraient retirée.

L'écriture était déclenchée par l'action `webpc_refresh_loader`, elle-même tirée
de six endroits :

| Déclencheur | Effet |
| --- | --- |
| Activation du plugin | Écrit les blocs |
| **`admin_init`, si la version du plugin diffère de l'option `webpc_latest_version`** | Écrit les blocs |
| Désactivation | **Retire** les blocs (fichiers ramenés à 0 octet) |
| Enregistrement de la page de réglages | Réécrit |
| Page de débogage | Réécrit |
| Détecteurs d'erreur (`RewritesErrorsDetector`, `PassthruExecutionDetector`) | Réécrivent, depuis l'admin |

**C'est la deuxième ligne qui surprend** : une simple mise à jour Composer ne
déclenche aucun hook d'activation, mais la première requête d'administration qui
suit constate l'écart de version et réécrit les fichiers. C'est exactement ce qui
s'est produit lors du passage en 6.6.5.

> **Leçon générale.** Un plugin qui gère des fichiers de configuration doit être
> **désactivé par WordPress avant** que ses fichiers ne soient supprimés :
> `dwp plugin deactivate` puis `dwp plugin uninstall`, et seulement ensuite
> `composer remove`. Dans l'autre ordre, le hook de désactivation ne peut plus
> s'exécuter et ses blocs restent en place pour toujours.

## Sur le serveur

Le déploiement ne relie plus `website/app/uploads-webpc`. Le `shared/uploads-webpc`
existant devient inerte et peut être supprimé à la main :

```bash
rm -rf ~/preprod-lcds/shared/uploads-webpc
```

L'exclusion rsync ayant disparu, le lien symbolique résiduel dans la release est
retiré au premier déploiement.
