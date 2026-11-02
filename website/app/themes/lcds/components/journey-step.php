<?php

/**
 * Une étape du parcours de soin, rendue dans un élément du slider.
 *
 * Arguments (via lcds_capture) :
 *   number   string Numéro sur deux chiffres.
 *   title    string Titre de l'étape.
 *   text     string Contenu riche : paragraphes et listes.
 *   duration string Durée indicative. Vide : la pastille n'est pas rendue.
 *   media    string Balisage du visuel. Vide : l'aplat tient la place.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$number = isset($args['number']) ? (string) $args['number'] : '';
$title = isset($args['title']) ? (string) $args['title'] : '';
$text = isset($args['text']) ? (string) $args['text'] : '';
$duration = isset($args['duration']) ? (string) $args['duration'] : '';
$media = isset($args['media']) ? (string) $args['media'] : '';
?>

<div class="journey__step">
    <div class="journey__media"><?php echo $media; ?></div>

    <p class="journey__number"><?php echo esc_html($number); ?></p>

    <div class="journey__body">
        <h3 class="journey__title"><?php echo esc_html($title); ?></h3>

        <?php if ($duration !== '') : ?>
            <p class="journey__duration"><?php echo esc_html($duration); ?></p>
        <?php endif; ?>

        <div class="journey__text">
            <?php echo wp_kses_post($text); ?>
        </div>
    </div>
</div>
