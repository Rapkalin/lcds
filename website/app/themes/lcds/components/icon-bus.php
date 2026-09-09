<?php

/**
 * Glyphe « transports » : un bus de trois quarts.
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

<svg class="icon-info-entry" width="24" height="24" viewBox="0 -2 26 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M5.20084 18.6577H2.85878C1.83645 18.6577 1 17.8213 1 16.799V4.71741C1 2.67283 2.6729 1 4.71756 1H15.8702C17.9149 1 19.5878 2.67283 19.5878 4.71741V9.0296C19.5878 9.79167 20.0525 10.4794 20.7588 10.7582L23.0637 11.6876C23.77 11.9664 24.2347 12.6541 24.2347 13.4161V16.799C24.2347 17.8213 23.3983 18.6577 22.376 18.6577H20.9819"/>
        <path d="M9.86621 18.6577H16.3162"/>
        <path d="M1 4.71741V9.36417H19.625C19.6064 9.25265 19.5878 9.14113 19.5878 9.02961V4.71741C19.5878 2.67283 17.9149 1 15.8702 1H4.71756C2.6729 1 1 2.67283 1 4.71741Z"/>
        <path d="M6.57617 1V9.36417"/>
        <path d="M13.082 1V9.36417"/>
        <path d="M7.5237 20.9813C9.01073 20.9813 9.86576 20.1449 9.86576 18.6393C9.86576 17.1338 9.02931 16.2974 7.5237 16.2974C6.01809 16.2974 5.18164 17.1338 5.18164 18.6393C5.18164 20.1449 6.01809 20.9813 7.5237 20.9813Z"/>
        <path d="M18.6399 21.0008C20.1269 21.0008 20.982 20.1644 20.982 18.6589C20.982 17.1533 20.1455 16.3169 18.6399 16.3169C17.1343 16.3169 16.2979 17.1533 16.2979 18.6589C16.2979 20.1644 17.1343 21.0008 18.6399 20.9822V21.0008Z"/>
    </g>
</svg>
