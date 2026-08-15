<?php

/**
 * Page 404.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="main-content" class="main-content notfound-container">
    <h1>404</h1>
    <p><?php esc_html_e("Il semblerait que la page que vous cherchez n'existe pas.", 'lcds'); ?></p>
    <p><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e("Retour à l'accueil", 'lcds'); ?></a></p>
</main>

<?php
get_footer();
