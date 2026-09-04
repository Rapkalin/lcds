<?php

/**
 * Section « l'histoire » : étiquette à gauche, texte et bouton d'action à
 * droite, sur fond bleu pâle.
 *
 * Arguments (via get_template_part) :
 *   label      string  Libellé de l'étiquette.
 *   dot        string  Couleur de la puce de l'étiquette.
 *   text       string  Contenu riche (paragraphes). Assaini par wp_kses_post :
 *                      c'est de la saisie de contributeur.
 *   cta        array   Arguments du bouton d'action (label, url).
 *   gallery    array   Arguments du carrousel (items, label). Absent : pas de
 *                      rail rendu.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$label = isset($args['label']) ? (string) $args['label'] : '';
$dot = isset($args['dot']) ? (string) $args['dot'] : 'turquoise';
$text = isset($args['text']) ? (string) $args['text'] : '';
$cta = isset($args['cta']) && is_array($args['cta']) ? $args['cta'] : [];
$gallery = isset($args['gallery']) && is_array($args['gallery']) ? $args['gallery'] : [];
?>

<section class="block-intro">
    <div class="block-intro__header">
        <div class="block-intro__label">
            <?php get_template_part('components/tag', null, ['label' => $label, 'dot' => $dot]); ?>
        </div>

        <div class="block-intro__content">
            <div class="block-intro__text">
                <?php echo wp_kses_post($text); ?>
            </div>

            <?php get_template_part('components/cta', null, $cta); ?>
        </div>
    </div>

    <?php if ($gallery !== []) : ?>
        <?php get_template_part('components/carousel', null, $gallery); ?>
    <?php endif; ?>
</section>
