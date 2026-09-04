<?php

/**
 * Outils de gabarit.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Rend un composant et retourne son balisage au lieu de l'afficher.
 *
 * `get_template_part()` écrit sur la sortie : il n'y a pas d'option pour
 * récupérer le résultat. Or le rail du carrousel a besoin du balisage de ses
 * cartes SOUS FORME DE CHAÎNE, pour rester un composant unique servant les deux
 * carrousels de la maquette.
 *
 * @param string $part Chemin du composant, sans extension.
 * @param array  $args Arguments passés au composant.
 */
function lcds_capture(string $part, array $args = []): string
{
    ob_start();
    get_template_part($part, null, $args);

    return (string) ob_get_clean();
}
