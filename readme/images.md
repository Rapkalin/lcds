# Images (WebP)

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

## Le plugin `webp-converter-for-media`

Le projet embarque aussi ce plugin, qui poursuit le **même objectif par un
mécanisme opposé** : des copies dans `app/uploads-webpc/` servies par des
réécritures `.htaccess`, au lieu d'une conversion à la génération.

État constaté : **actif, jamais configuré** (aucune option `webpc_settings` en
base) et **zéro média converti** — le seul fichier présent dans
`app/uploads-webpc/` est sa propre icône d'autotest. Ses règles de réécriture
sont en revanche bien inscrites dans le `website/.htaccess` versionné, et
s'évaluent à chaque requête d'image (plusieurs tests de présence de fichier)
sans jamais aboutir, faute de copies à servir.

Le module natif rendant le plugin superflu, son retrait libérerait :

- une dépendance Composer ;
- **30 lignes de réécritures dans le `.htaccess` versionné** — le fichier
  responsable de trois pannes de préprod, voir [`securite.md`](securite.md) ;
- le répertoire `shared/uploads-webpc`, son lien symbolique et son exclusion
  rsync dans [`deploiement.md`](deploiement.md) ;
- le doublonnage de chaque image sur le disque, s'il venait à être configuré.

Contrepartie : le plugin sait produire de l'**AVIF**, ce que le filtre natif ne
fait pas. Le gain d'AVIF sur WebP est de l'ordre de 10 à 20 % de poids.
