<?php

/**
 * Étiquette de section : pastille contournée, puce de couleur, libellé court.
 *
 * Arguments (via get_template_part) :
 *   label string  Libellé, rendu en capitales par le CSS.
 *   dot   string  Couleur de la puce, parmi les valeurs de LcdsDotColor.
 *                 Elle change d'une section à l'autre dans la maquette. Les
 *                 libellés vus par le contributeur (« Vert », « Rouge ») ne
 *                 correspondent pas à ces valeurs — voir l'enum.
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

$dot = LcdsDotColor::fromValue($args['dot'] ?? '', LcdsDotColor::Turquoise);
?>

<p class="tag tag--<?php echo esc_attr($dot->value); ?>">
    <span class="tag__dot" aria-hidden="true"></span>
    <?php echo esc_html($label); ?>
</p>
