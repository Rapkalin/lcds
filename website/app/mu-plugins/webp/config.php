<?php

/**
 * LCDS — images WebP : configuration.
 *
 * Source unique de vérité des réglages du module. Ne changer les valeurs qu'ici.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Qualité d'encodage WebP des sous-tailles générées (0-100). 82 est un bon
 * compromis poids/qualité sur du contenu photographique.
 */
const LCDS_WEBP_QUALITY = 82;

/**
 * Types MIME sources dont les sous-tailles générées sont converties en WebP. Le
 * fichier téléversé est conservé tel quel. Les SVG et GIF sont volontairement
 * exclus : le vectoriel et l'animation ne se prêtent pas à ce traitement.
 *
 * @var string[]
 */
const LCDS_WEBP_SOURCE_FORMATS = ['image/jpeg', 'image/png'];
