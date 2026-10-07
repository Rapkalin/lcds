<?php

/**
 * Glyphe du bouton d'arrêt de la vidéo : « pause » pendant la lecture,
 * « lecture » à l'arrêt.
 *
 * Un seul fichier pour les deux états, comme `icon-plus` : c'est le CSS qui
 * masque celui qui ne sert pas, selon la classe posée sur le hero. Deux
 * fichiers auraient divergé.
 *
 * Même boîte de 20 que les autres glyphes de bouton rond du site.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<svg class="icon-motion" width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <g class="icon-motion__pause">
        <rect x="6" y="4.5" width="2.5" height="11" rx="1.25"/>
        <rect x="11.5" y="4.5" width="2.5" height="11" rx="1.25"/>
    </g>
    <path class="icon-motion__play" d="M6.75 4.9a.9.9 0 0 1 1.37-.77l7.1 4.63a.9.9 0 0 1 0 1.51l-7.1 4.63a.9.9 0 0 1-1.37-.77Z"/>
</svg>
