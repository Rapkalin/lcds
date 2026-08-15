<?php

/**
 * Gabarit générique — sert aussi de page d'accueil.
 *
 * Le thème n'a pas de front-page.php : WordPress retombe sur ce fichier pour la
 * page d'accueil comme pour tout contenu sans gabarit dédié.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="main-content" class="main-content">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class(); ?>>
                <h1 class="entry-title"><?php the_title(); ?></h1>
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <p><?php esc_html_e('Aucun contenu à afficher.', 'lcds'); ?></p>
    <?php endif; ?>
</main>

<?php
get_footer();
