<?php

/**
 * Flèche des contrôles de carrousel, en SVG inline.
 *
 * Tracé fermé aux arêtes incurvées, contour seul, en `currentColor`. Les
 * coordonnées sont celles du bouton de 52px de la maquette (d'où le viewBox
 * décalé) : glyphe de 17,4 × 16,6 centré, trait de 1,5.
 *
 * Le sens « suivant » est obtenu par une symétrie CSS, pas par un second tracé :
 * les deux flèches de la maquette sont l'image miroir l'une de l'autre.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<svg class="icon-arrow" width="20" height="20" viewBox="16 16 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <path d="M33.4 18.6 Q19.6 22 17.95 25.9 Q19.6 29.8 33.4 33.2 L31.3 25.9 Z"
          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
