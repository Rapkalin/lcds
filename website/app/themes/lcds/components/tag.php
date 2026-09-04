<?php

/**
 * Étiquette de section : pastille contournée, puce de couleur, libellé court.
 *
 * Arguments (via get_template_part) :
 *   label string  Libellé, rendu en capitales par le CSS.
 *   element string Balise portant l'étiquette. `h2` quand elle nomme une
 *                   section : c'est le SEUL libellé visible de la section, donc
 *                   c'est lui qui doit apparaître dans le plan de titres. Sans
 *                   ça, un utilisateur qui navigue par titres reçoit les titres
 *                   d'items en vrac, sans regroupement. `p` par défaut.
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

// Allow-list : la balise finit dans le balisage, elle ne peut pas venir
// librement de l'appelant.
$element = isset($args['element']) && in_array($args['element'], ['h2', 'h3', 'p'], true)
    ? (string) $args['element']
    : 'p';

$dot = LcdsDotColor::fromValue($args['dot'] ?? '', LcdsDotColor::Turquoise);
?>

<<?php echo $element; ?> class="tag tag--<?php echo esc_attr($dot->value); ?>">
    <span class="tag__dot" aria-hidden="true"></span>
    <?php echo esc_html($label); ?>
</<?php echo $element; ?>>
