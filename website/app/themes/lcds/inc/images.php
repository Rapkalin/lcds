<?php

/**
 * Affichage des images de contenu.
 *
 * Point d'entrée unique, pour que toute image profite du WebP et du srcset
 * responsive. La conversion elle-même est de l'infrastructure et vit dans le
 * mu-plugin lcds-webp — voir readme/images.md.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Construit le balisage <img> d'une image de contenu.
 *
 * Un ATTACHEMENT (identifiant de médiathèque ou tableau image ACF) passe par
 * wp_get_attachment_image() : srcset responsive servi en WebP, dimensions
 * intrinsèques, chargement différé natif et texte alternatif de la médiathèque.
 * Une simple URL retombe sur un <img> nu — il n'y a rien à convertir.
 *
 * @param int|array|string $image Identifiant d'attachement, tableau image ACF
 *                                (sa clé 'ID' est utilisée), ou URL.
 * @param array            $attr  Attributs <img> supplémentaires ('class',
 *                                'alt', 'sizes'…). 'alt' remplace celui de
 *                                l'attachement.
 * @param string           $size  Taille d'image enregistrée (cas attachement).
 * @return string Balisage de l'<img>, chaîne vide s'il n'y a rien à afficher.
 */
function lcds_render_image(int|array|string $image, array $attr = [], string $size = 'large'): string
{
    $attachment_id = 0;

    if (is_int($image)) {
        $attachment_id = $image;
    } elseif (is_array($image) && !empty($image['ID'])) {
        $attachment_id = (int) $image['ID'];
    }

    if ($attachment_id > 0) {
        return wp_get_attachment_image($attachment_id, $size, false, $attr);
    }

    $url = is_array($image) ? (string) ($image['url'] ?? '') : (string) $image;

    if ($url === '') {
        return '';
    }

    // `alt` par défaut UNIQUEMENT sur cette branche : sans attachement, il n'y a
    // aucun texte alternatif à aller chercher. Sur la branche attachement,
    // wp_get_attachment_image() lit `_wp_attachment_image_alt` dès qu'on ne le
    // lui impose pas — c'est ce qui rend l'alternative contribuable depuis la
    // médiathèque. Ne JAMAIS y passer 'alt' => '' : toutes les images du site
    // redeviendraient décoratives d'office.
    $attr = array_merge(['loading' => 'lazy', 'decoding' => 'async', 'alt' => ''], $attr);
    $html = '<img src="' . esc_url($url) . '"';

    foreach ($attr as $name => $value) {
        $html .= sprintf(' %s="%s"', esc_attr((string) $name), esc_attr((string) $value));
    }

    return $html . ' />';
}
