<?php

/**
 * Section « l'histoire » : étiquette à gauche, texte et bouton d'action à
 * droite, sur fond bleu pâle.
 *
 * Arguments (via get_template_part) :
 *   label      string  Libellé de l'étiquette.
 *   dot        string  Couleur de la puce de l'étiquette.
 *   paragraphs array   Paragraphes, un par entrée.
 *   cta        array   Arguments du bouton d'action (label, url).
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$label = isset($args['label']) ? (string) $args['label'] : '';
$dot = isset($args['dot']) ? (string) $args['dot'] : 'turquoise';
$paragraphs = isset($args['paragraphs']) && is_array($args['paragraphs']) ? $args['paragraphs'] : [];
$cta = isset($args['cta']) && is_array($args['cta']) ? $args['cta'] : [];
?>

<section class="block-intro">
    <div class="block-intro__header">
        <div class="block-intro__label">
            <?php get_template_part('components/tag', null, ['label' => $label, 'dot' => $dot]); ?>
        </div>

        <div class="block-intro__content">
            <div class="block-intro__text">
                <?php foreach ($paragraphs as $paragraph) : ?>
                    <p><?php echo esc_html((string) $paragraph); ?></p>
                <?php endforeach; ?>
            </div>

            <?php get_template_part('components/cta', null, $cta); ?>
        </div>
    </div>
</section>
