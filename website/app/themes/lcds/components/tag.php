<?php

/**
 * Étiquette de section : pastille contournée, puce de couleur, libellé court.
 *
 * Arguments (via get_template_part) :
 *   label string  Libellé, rendu en capitales par le CSS.
 *   dot   string  Couleur de la puce : 'turquoise' (défaut) ou 'orange'. Elle
 *                 change d'une section à l'autre dans la maquette.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$label = isset($args['label']) ? (string) $args['label'] : '';

if ($label === '') {
    return;
}

$dot = ($args['dot'] ?? '') === 'orange' ? 'orange' : 'turquoise';
?>

<p class="tag tag--<?php echo esc_attr($dot); ?>">
    <span class="tag__dot" aria-hidden="true"></span>
    <?php echo esc_html($label); ?>
</p>
