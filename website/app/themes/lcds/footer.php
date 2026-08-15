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
</footer>

<?php wp_footer(); ?>
</body>
</html>
