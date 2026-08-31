<?php

/**
 * LCDS — images WebP : filtres de conversion.
 *
 * S'appuie sur le pipeline d'images natif de WordPress (depuis la 5.8) : aucun
 * plugin tiers, aucun service externe. Seules les sous-tailles GÉNÉRÉES sont
 * converties, le fichier téléversé reste dans son format. Exige une bibliothèque
 * GD ou Imagick compilée avec le support WebP.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Associe les sources JPEG/PNG à une sortie WebP pour les sous-tailles générées
 * par WordPress. Branché sur image_editor_output_format (WP 5.8+).
 *
 * @param array $formats Table type MIME source => type MIME de sortie.
 */
function lcds_webp_output_format(array $formats): array
{
    foreach (LCDS_WEBP_SOURCE_FORMATS as $mime) {
        $formats[$mime] = 'image/webp';
    }

    return $formats;
}
add_filter('image_editor_output_format', 'lcds_webp_output_format');

/**
 * Applique la qualité configurée à l'encodage WebP, sans toucher aux autres
 * formats.
 *
 * @param int    $quality   Qualité par défaut de l'éditeur d'images.
 * @param string $mime_type Type MIME visé.
 */
function lcds_webp_quality(int $quality, string $mime_type): int
{
    return $mime_type === 'image/webp' ? LCDS_WEBP_QUALITY : $quality;
}
add_filter('wp_editor_set_quality', 'lcds_webp_quality', 10, 2);
