<?php

/**
 * Page d'accueil.
 *
 * WordPress donne la priorité à ce gabarit sur index.php pour la page d'accueil.
 *
 * Le contenu n'est plus écrit ici : il vient des blocs de la page, saisis dans
 * l'éditeur — voir inc/blocks.php. Ce gabarit ne fait plus que le dérouler.
 *
 * Syntaxe alternative pour la boucle : Pint désaligne une accolade noyée dans
 * du balisage (`statement_indentation`), et c'est de toute façon la convention
 * des gabarits WordPress.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="main-content" class="main-content front-page">
    <?php while (have_posts()) : ?>
        <?php the_post(); ?>
        <?php the_content(); ?>
    <?php endwhile; ?>
</main>

<?php
get_footer();
