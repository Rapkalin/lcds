<?php

/**
 * Bouton d'action : deux pastilles jointes, l'icône puis le libellé.
 *
 * Arguments (via get_template_part) :
 *   label string  Libellé, rendu en capitales par le CSS.
 *   url   string  Destination. Vide : rien n'est rendu, plutôt qu'un lien mort.
 *   icon    string Glyphe de la pastille de gauche. La maquette utilise un
 *                  émoji — voir readme/front.md pour la réserve que ça pose.
 *   variant string Valeur d'un cas de `LcdsCtaVariant`. Défaut : la primaire,
 *                  les deux pastilles du hero et des sections. La secondaire
 *                  est une SEULE pastille contournée, sans glyphe — le « voir
 *                  le plan » des informations pratiques et les boutons du pied
 *                  de page, mesurés 131 × 30 sur le PDF contre 321 × 30 pour
 *                  la primaire.
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
$variant = LcdsCtaVariant::fromValue($args['variant'] ?? null, LcdsCtaVariant::Primary);
?>

<a class="<?php echo esc_attr($variant->className()); ?>" href="<?php echo esc_url($url); ?>">
    <?php if ($variant->hasIcon()) : ?>
        <?php
        /*
         * La silhouette, tracée par `initPillShape`. Elle vient AVANT les
         * pastilles et reste vide : sans JavaScript elle ne peint rien, et ce
         * sont leurs fonds qui portent le bleu.
         */
        ?>
        <svg class="cta__shape" aria-hidden="true" focusable="false"><path d="" /></svg>
        <span class="cta__icon" aria-hidden="true"><?php echo esc_html($icon); ?></span>
    <?php endif; ?>
    <span class="cta__label"><?php echo esc_html($label); ?></span>
</a>
