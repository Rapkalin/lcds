<?php

/**
 * Bouton d'action : deux pastilles jointes, l'icône puis le libellé.
 *
 * Arguments (via get_template_part) :
 *   label string  Libellé, rendu en capitales par le CSS.
 *   url   string  Destination. Vide : rien n'est rendu, plutôt qu'un lien mort.
 *   icon    string Glyphe de la pastille de gauche. La maquette utilise un
 *                  émoji — voir readme/front.md pour la réserve que ça pose.
 *   variant string `solid` (défaut) : les deux pastilles jointes du hero et des
 *                  sections. `outline` : une seule pastille contournée, sans
 *                  glyphe — le « voir le plan » des informations pratiques,
 *                  mesuré 131 × 30 sur le PDF contre 321 × 30 pour le solide.
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
$variant = ($args['variant'] ?? '') === 'outline' ? 'outline' : 'solid';
?>

<a class="cta cta--<?php echo esc_attr($variant); ?>" href="<?php echo esc_url($url); ?>">
    <?php if ($variant === 'solid') : ?>
        <span class="cta__icon" aria-hidden="true"><?php echo esc_html($icon); ?></span>
    <?php endif; ?>
    <span class="cta__label"><?php echo esc_html($label); ?></span>
</a>
