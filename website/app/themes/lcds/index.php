<?php

/**
 * Gabarit générique : contenu isolé, archives et résultats de recherche.
 *
 * La page d'accueil a son propre gabarit (front-page.php) ; WordPress retombe
 * ici pour tout le reste.
 *
 * Le NIVEAU du titre dépend du contexte, et ce n'est pas cosmétique : sur une
 * liste, chaque résultat portait un `h1`, donc autant de `h1` que de résultats
 * — mesuré : deux sur `?s=`. Une page n'a qu'un seul `h1`, ici celui de la
 * liste elle-même.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$is_single = is_singular();
?>

<main id="main-content" class="main-content">
    <?php if (! $is_single) : ?>
        <h1 class="archive-title">
            <?php if (is_search()) : ?>
                <?php printf(
                    /* translators: %s : termes recherchés. */
                    esc_html__('Résultats de recherche pour « %s »', 'lcds'),
                    esc_html(get_search_query()),
                ); ?>
            <?php elseif (is_archive()) : ?>
                <?php echo esc_html(wp_strip_all_tags(get_the_archive_title())); ?>
            <?php else : ?>
                <?php echo esc_html(get_bloginfo('name')); ?>
            <?php endif; ?>
        </h1>
    <?php endif; ?>

    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : ?>
            <?php the_post(); ?>
            <article <?php post_class(); ?>>
                <?php if ($is_single) : ?>
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                <?php else : ?>
                    <h2 class="entry-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                <?php endif; ?>

                <div class="entry-content">
                    <?php if ($is_single) : ?>
                        <?php the_content(); ?>
                    <?php else : ?>
                        <?php the_excerpt(); ?>
                    <?php endif; ?>
                </div>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <p><?php esc_html_e('Aucun contenu à afficher.', 'lcds'); ?></p>
    <?php endif; ?>
</main>

<?php
get_footer();
