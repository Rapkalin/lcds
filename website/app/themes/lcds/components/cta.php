<?php

/**
 * Bouton d'action : deux pastilles jointes, l'icône puis le libellé.
 *
 * Arguments (via get_template_part) :
 *   label string  Libellé, rendu en capitales par le CSS.
 *   url   string  Destination. Vide : rien n'est rendu, plutôt qu'un lien mort.
 *   icon  string  Glyphe de la pastille de gauche. La maquette utilise un
 *                 émoji — voir readme/front.md pour la réserve que ça pose.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$label = isset($args['label']) ? (string) $args['label'] : '';
$url = isset($args['url']) ? (string) $args['url'] : '';

if ($label === '' || $url === '') {
    return;
}

$icon = isset($args['icon']) ? (string) $args['icon'] : '🦷';
?>

<a class="cta" href="<?php echo esc_url($url); ?>">
    <span class="cta__icon" aria-hidden="true"><?php echo esc_html($icon); ?></span>
    <span class="cta__label"><?php echo esc_html($label); ?></span>
</a>
