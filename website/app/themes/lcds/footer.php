<?php

/**
 * Pied de page.
 *
 * Un panneau à coins arrondis posé PAR-DESSUS un visuel pleine largeur. En fin
 * de page le panneau se soulève et découvre le visuel — voir `initFooterReveal`
 * dans assets/scripts/app.js.
 *
 * Sans JavaScript, ou si l'utilisateur demande à réduire les animations, le
 * panneau reste posé et le visuel est simplement visible en dessous : c'est
 * exactement ce que dessine la maquette, et rien n'est inaccessible.
 *
 * Le contenu vient des « Réglages du site » (page d'options ACF) : le pied de
 * page est commun à toutes les pages, il n'appartient à aucune d'elles.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$blocks = lcds_option('blocs');
$blocks = is_array($blocks) ? $blocks : [];
$overline = lcds_option_text('surtitre');
$address = (string) lcds_option('adresse');
$copyright = lcds_option_text('copyright');
$reveal = lcds_attachment_id(lcds_option('visuel'));

$visual = $reveal === 0 ? '' : lcds_render_image($reveal, ['class' => 'footer-reveal__image'], 'full');
?>

<div class="footer-reveal"<?php echo $visual === '' ? '' : ' data-footer-reveal'; ?>>
    <?php if ($visual !== '') : ?>
        <div class="footer-reveal__media"><?php echo $visual; ?></div>
    <?php endif; ?>

    <footer id="site-footer" class="site-footer">
        <div class="site-footer__inner">
            <div class="site-footer__calls">
                <?php foreach ($blocks as $block) : ?>
                    <?php get_template_part('components/footer-call', null, $block); ?>
                <?php endforeach; ?>

                <a class="site-footer__logo" href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                    <?php get_template_part('components/site-logo'); ?>
                    <span class="screen-reader-text"><?php echo esc_html(get_bloginfo('name')); ?></span>
                </a>
            </div>

            <div class="site-footer__aside">
                <?php if ($overline !== '' || $address !== '') : ?>
                    <div class="site-footer__address">
                        <?php if ($overline !== '') : ?>
                            <p class="site-footer__overline"><?php echo esc_html($overline); ?></p>
                        <?php endif; ?>

                        <?php echo wp_kses_post($address); ?>
                    </div>
                <?php endif; ?>

                <?php lcds_footer_nav(); ?>
                <?php lcds_footer_legal(); ?>

                <?php if ($copyright !== '' || lcds_site_version() !== '') : ?>
                    <p class="site-footer__copyright">
                        <?php if ($copyright !== '') : ?>
                            <?php echo esc_html(sprintf($copyright, wp_date('Y'))); ?>
                        <?php endif; ?>

                        <?php if (lcds_site_version() !== '') : ?>
                            <span class="site-footer__version">
                                <?php printf(
                                    /* translators: %s : numéro de version du site. */
                                    esc_html__('Version %s', 'lcds'),
                                    esc_html(lcds_site_version()),
                                ); ?>
                            </span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
