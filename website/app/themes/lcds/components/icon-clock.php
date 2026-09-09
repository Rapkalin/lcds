<?php

/**
 * Glyphe « horaires » : une horloge.
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
        <path d="M13 6.39111V13.3374H20.279"/>
        <path d="M3.72407 21.2409C7.9875 26.253 18.0118 26.253 22.2752 21.2409C26.2059 16.6239 25.8939 7.59792 21.1729 3.66724C16.9511 0.110919 9.04816 0.110919 4.80552 3.66724C0.0845538 7.59792 -0.206607 16.6239 3.72407 21.2409Z"/>
    </g>
</svg>
