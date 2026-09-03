<?php

/**
 * Glyphe d'ouverture d'un panneau : « + » fermé, « − » ouvert.
 *
 * Un seul tracé pour les deux états : la barre verticale est masquée par le CSS
 * quand le panneau est ouvert. Deux fichiers auraient divergé.
 *
 * Coordonnées dans l'espace du bouton de 52px de la maquette (d'où le viewBox
 * décalé) : glyphe de 17,5 × 17,5 centré, trait de 1,5.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<svg class="icon-plus" width="20" height="20" viewBox="16 16 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <g stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
        <line x1="17.95" y1="25.94" x2="33.93" y2="25.94"/>
        <line class="icon-plus__bar" x1="25.94" y1="17.95" x2="25.94" y2="33.93"/>
    </g>
</svg>
