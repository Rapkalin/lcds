<?php

/**
 * Glyphe « information » : un « i » cerclé.
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
        <path d="M3.71894 21.2386C7.9742 26.2538 18.0235 26.2538 22.2787 21.2386C26.2111 16.6224 25.9071 7.59899 21.1769 3.64767C16.9407 0.114282 9.05702 0.114282 4.82075 3.66667C0.0905595 7.59899 -0.213388 16.6414 3.71894 21.2386Z"/>
        <path d="M10.6143 11.0938H11.5641C12.6089 11.0938 13.4638 11.9486 13.4638 12.9934V18.1985"/>
        <path d="M10.6533 18.1997H16.3143"/>
        <path d="M13.4844 6.76367V7.37157"/>
    </g>
</svg>
