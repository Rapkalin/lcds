<?php

/**
 * Glyphe « transports » : un bus de profil.
 *
 * Redessin d'après la maquette : 24 × 24, trait de 1,5, en `currentColor`.
 * Décoratif — l'entrée porte déjà son titre.
 *
 * PROPORTIONS relevées au pixel sur `HP_06_Frame 54.pdf`, icône à y=2124 : la
 * caisse y occupe environ 15 des 22 pixels d'encre, et les roues CHEVAUCHENT
 * son bas. Le premier tracé lui donnait 8,5 de haut sur 24 — un tiers de la
 * boîte — avec des roues détachées deux pixels plus bas. D'où l'impression de
 * bus écrasé flottant au-dessus de ses roues.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<svg class="icon-info-entry" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <g stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3.25" y="2.75" width="17.5" height="14.5" rx="2.75"/>
        <path d="M3.25 10.75h17.5M9.5 2.75v8M14.5 2.75v8"/>
        <circle cx="8" cy="18.25" r="1.75"/>
        <circle cx="16" cy="18.25" r="1.75"/>
    </g>
</svg>
