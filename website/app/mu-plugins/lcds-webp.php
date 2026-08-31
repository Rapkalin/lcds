<?php

/**
 * LCDS — images WebP (point d'entrée du module).
 *
 * Le traitement des images est de l'INFRASTRUCTURE : il vit dans un mu-plugin,
 * donc toujours chargé (front, admin, WP-CLI, cron), il survit à un changement
 * de thème et s'applique à tout le site. Ce fichier n'est qu'un INDEX : il
 * `require_once` les parties du module. Pour l'étendre, ajouter un fichier sous
 * webp/ et le requérir ici.
 *
 * Ce qu'il fait : chaque téléversement JPEG/PNG garde son fichier ORIGINAL
 * intact, mais les sous-tailles générées par WordPress (miniature, moyenne,
 * grande…) sont encodées en WebP. Tout code affichant une image via
 * wp_get_attachment_image() — dont lcds_render_image() du thème — sert alors du
 * WebP automatiquement, par srcset. Les SVG et GIF ne sont jamais convertis.
 *
 * Les médias déjà en base reçoivent leurs sous-tailles WebP en régénérant les
 * miniatures :
 *     wp media regenerate --yes
 *
 * Fichiers du module :
 *  - webp/config.php     → qualité + formats sources (source unique de vérité) ;
 *  - webp/conversion.php → les filtres WordPress qui font la conversion.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

// Inclusions simples, sans en-tête « Plugin Name », pour que l'autoloader
// Bedrock les ignore : elles ne se chargent que depuis ici. La configuration
// d'abord, elle définit les constantes que lisent les filtres.
require_once __DIR__ . '/webp/config.php';
require_once __DIR__ . '/webp/conversion.php';
