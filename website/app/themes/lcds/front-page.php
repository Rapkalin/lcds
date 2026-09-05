<?php

/**
 * Page d'accueil.
 *
 * Le contenu est un champ ACF de contenu flexible : ses layouts sont les
 * sections, et un contributeur les ajoute, réordonne et supprime depuis un
 * unique formulaire sous l'éditeur — voir readme/contribution.md.
 *
 * Ce fichier reste DÉCLARATIF : il ne lit aucun champ de section. Chaque layout
 * a son gabarit dans `layouts/`, qui lit ses sous-champs et délègue au
 * composant. Le nom du layout EST le nom du fichier : `get_row_layout()`
 * suffit, aucun `switch` à tenir à jour.
 *
 * Le titre `h1` est un champ de la PAGE et non d'une section : les maquettes ne
 * prévoient aucun titre visible, il est donc rendu masqué visuellement.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$page_id = is_preview() ? (int) get_queried_object_id() : (int) get_the_ID();
$heading = lcds_field_text('titre_h1');
?>

<main id="main-content" class="main-content front-page">
    <?php if ($heading !== '') : ?>
        <h1 class="screen-reader-text"><?php echo esc_html($heading); ?></h1>
    <?php endif; ?>

    <?php if (have_rows('sections', $page_id)) : ?>
        <?php while (have_rows('sections', $page_id)) : ?>
            <?php the_row(); ?>
            <?php get_template_part('layouts/' . lcds_layout_template()); ?>
        <?php endwhile; ?>
    <?php endif; ?>
</main>

<?php
get_footer();
