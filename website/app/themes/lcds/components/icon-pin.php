<?php

/**
 * Glyphe « adresse » : une cible de localisation.
 *
 * Redessin d'après la maquette : 24 × 24, trait de 1,5, en `currentColor`.
 * Décoratif — l'entrée porte déjà son titre.
 *
 * DIAMÈTRE relevé au pixel sur `HP_06_Frame 54.pdf`, icône à y=1954 : l'anneau
 * y occupe presque toute la boîte, et les quatre repères ne dépassent que de
 * trois pixels. Le premier tracé lui donnait un rayon de 4,25 sur une boîte de
 * 24 — un anneau deux fois trop petit, perdu au milieu de ses repères.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<svg class="icon-info-entry" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <g stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="8.75"/>
        <path d="M12 0.75v2.5M12 20.75v2.5M0.75 12h2.5M20.75 12h2.5"/>
    </g>
</svg>
