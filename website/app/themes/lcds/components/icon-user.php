<?php

/**
 * Glyphe « contact » : un buste cerclé.
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
        <path d="M19.8313 22.2855C19.4436 20.8455 18.6313 19.5716 17.4682 18.6301C16.2128 17.5962 14.6251 17.0239 12.982 17.0239C11.339 17.0239 9.76974 17.5962 8.49589 18.6301C7.33281 19.5716 6.5205 20.8639 6.13281 22.2855"/>
        <path d="M13 25C20.68 25 25 20.68 25 13C25 5.32 20.68 1 13 1C5.32 1 1 5.32 1 13C1 20.68 5.32 25 13 25Z"/>
        <path d="M13.0021 14.0705C15.5867 14.0705 17.0451 12.6121 17.0451 10.0275C17.0451 7.44284 15.5867 5.98438 13.0021 5.98438C10.4174 5.98438 8.95898 7.44284 8.95898 10.0275C8.95898 12.6121 10.4174 14.0705 13.0021 14.0705Z"/>
    </g>
</svg>
