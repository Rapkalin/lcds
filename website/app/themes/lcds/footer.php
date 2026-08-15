<?php

/**
 * Pied de page du site.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<footer id="site-footer" class="site-footer">
    <?php wp_nav_menu(lcds_nav_menu_args('footer-menu', 'footer-navigation', __('Navigation de pied de page', 'lcds'))); ?>
    <?php wp_nav_menu(lcds_nav_menu_args('social-menu', 'social-navigation', __('Réseaux sociaux', 'lcds'))); ?>

    <div class="footer-legal">
        <p>&copy; <?php echo esc_html(wp_date('Y')); ?> <?php bloginfo('name'); ?></p>
        <?php wp_nav_menu(lcds_nav_menu_args('legal-menu', 'legal-navigation', __('Mentions légales', 'lcds'))); ?>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
