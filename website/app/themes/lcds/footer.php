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
    <p class="footer-legal">&copy; <?php bloginfo('name'); ?> <?php echo esc_html(wp_date('Y')); ?></p>

    <?php if (lcds_site_version() !== '') : ?>
        <p class="footer-version">
            <?php printf(
                /* translators: %s : numéro de version du site. */
                esc_html__('Version %s', 'lcds'),
                esc_html(lcds_site_version()),
            ); ?>
        </p>
    <?php endif; ?>
</footer>

<?php wp_footer(); ?>
</body>
</html>
