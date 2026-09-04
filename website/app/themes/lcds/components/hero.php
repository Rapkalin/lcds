<?php

/**
 * Hero de la page d'accueil : visuel pleine largeur, carte d'appel en bas à
 * droite.
 *
 * Arguments (via get_template_part) :
 *   image      int|array  Visuel de fond — identifiant d'attachement ou tableau
 *                         ACF. Vide : un aplat tient la place.
 *   thumbnail  int|array  Vignette de la carte, même règle.
 *   label      string     Libellé court de la carte, en capitales.
 *   text       string     Phrase d'accroche de la carte.
 *   url        string     Destination de la carte. Vide : rien n'est cliquable,
 *                         plutôt qu'un lien mort.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$image = $args['image'] ?? 0;
$thumbnail = $args['thumbnail'] ?? 0;
$label = isset($args['label']) ? (string) $args['label'] : '';
$text = isset($args['text']) ? (string) $args['text'] : '';
$url = isset($args['url']) ? (string) $args['url'] : '';

$background = $image === 0 ? '' : lcds_render_image($image, [
    'class' => 'hero__image',
    'loading' => 'eager',
    'fetchpriority' => 'high',
], 'full');

$vignette = $thumbnail === 0 ? '' : lcds_render_image($thumbnail, [
    'class' => 'hero__thumbnail-image',
], 'medium');

$card_tag = $url === '' ? 'div' : 'a';
?>

<section class="hero">
    <?php if ($background !== '') : ?>
        <?php echo $background; ?>
    <?php else : ?>
        <div class="hero__image hero__image--placeholder" aria-hidden="true"></div>
    <?php endif; ?>

    <<?php echo $card_tag; ?> class="hero__card"<?php echo $url === '' ? '' : ' href="' . esc_url($url) . '"'; ?>>
        <div class="hero__thumbnail">
            <?php if ($vignette !== '') : ?>
                <?php echo $vignette; ?>
            <?php endif; ?>
        </div>

        <div class="hero__card-body">
            <p class="hero__card-label">
                <?php echo esc_html($label); ?>
                <?php get_template_part('components/icon-calendar'); ?>
            </p>

            <?php if ($text !== '') : ?>
                <p class="hero__card-text"><?php echo esc_html($text); ?></p>
            <?php endif; ?>
        </div>
    </<?php echo $card_tag; ?>>
</section>
