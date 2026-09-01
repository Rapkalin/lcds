<?php

/**
 * Icône calendrier, en SVG inline.
 *
 * Les tracés sont en `currentColor` : contrairement au logo, c'est un élément
 * d'interface, il suit la couleur du texte qui l'accompagne.
 *
 * Le viewBox de 17.1541 × 17.5 englobe le débord du contour (épaisseur 1.5) du
 * dessin de 15.654 × 16 : le réduire rognerait les bords arrondis.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<svg class="icon-calendar" width="17.1541" height="17.5" viewBox="0 0 17.1541 17.5" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <g stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M1.0062 13.2656C1.21117 15.1103 2.70997 16.5707 4.56745 16.6603C5.84848 16.7244 7.15512 16.75 8.57706 16.75C9.999 16.75 11.3056 16.7116 12.5867 16.6603C14.4442 16.5707 15.943 15.1103 16.1479 13.2656C16.2888 12.023 16.4041 10.7548 16.4041 9.44815C16.4041 8.14151 16.2888 6.87329 16.1479 5.6307C15.943 3.78602 14.4442 2.32566 12.5867 2.23598C11.3056 2.17193 9.999 2.14631 8.57706 2.14631C7.15512 2.14631 5.84848 2.18474 4.56745 2.23598C2.70997 2.32566 1.21117 3.78602 1.0062 5.6307C0.865292 6.87329 0.75 8.14151 0.75 9.44815C0.75 10.7548 0.865292 12.023 1.0062 13.2656Z"/>
        <path d="M4.91333 0.75V3.95256"/>
        <path d="M11.959 0.75V3.95256"/>
        <path d="M0.878071 6.83487H16.2632"/>
    </g>
</svg>
