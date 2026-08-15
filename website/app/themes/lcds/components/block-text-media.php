<?php

/**
 * Bloc deux colonnes : média à gauche, texte à droite.
 *
 * Le média est un aplat en attendant les visuels : sa hauteur vient d'un
 * `aspect-ratio` en CSS, un conteneur vide n'en ayant aucune par lui-même.
 *
 * Arguments (via get_template_part) :
 *   title string  Titre de la section.
 *   text  string  Texte d'accompagnement.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$title = isset($args['title']) ? (string) $args['title'] : '';
$text = isset($args['text']) ? (string) $args['text'] : '';
?>

<section class="block-text-media">
    <div class="block-text-media__media" aria-hidden="true"></div>

    <div class="block-text-media__content">
        <?php if ($title !== '') : ?>
            <h2><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <?php if ($text !== '') : ?>
            <p><?php echo esc_html($text); ?></p>
        <?php endif; ?>
    </div>
</section>
