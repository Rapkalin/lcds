<?php

/**
 * Glyphe « adresse » : un repère de visée.
 *
 * Tracé FOURNI PAR LE CLIENT, repris tel quel. Ce qui a changé de son export :
 *
 *   - `stroke="#048B8C"` répété sur chaque tracé devient un `currentColor`
 *     hissé sur le `<g>`. La teinte vient alors de la feuille de style
 *     (`.block-info__icon`), donc du jeton, et le glyphe se recolore où qu'il
 *     serve. Un turquoise en dur aurait figé une valeur que la bibliothèque
 *     Figma est seule à porter ;
 *   - la boîte rendue est ramenée à 24 pour tenir dans la colonne d'icône,
 *     que la maquette mesure à 24. Le `viewBox` garde ses 26 : c'est lui qui
 *     porte le dessin, et le rapport est conservé ;
 *   - `aria-hidden` et `focusable` : le glyphe est décoratif, l'entrée porte
 *     déjà son titre.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<svg class="icon-info-entry" width="24" height="24" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M13 4.66667V1"/>
        <path d="M13 24.9997V21.333"/>
        <path d="M21.333 13H24.9995"/>
        <path d="M1 13H4.66652"/>
        <path d="M12.998 21.3337C18.3312 21.3337 21.331 18.3337 21.331 13.0003C21.331 7.667 18.3312 4.66699 12.998 4.66699C7.66492 4.66699 4.66504 7.667 4.66504 13.0003C4.66504 18.3337 7.66492 21.3337 12.998 21.3337Z"/>
    </g>
</svg>
