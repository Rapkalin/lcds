<?php

/**
 * Pied de page.
 *
 * Un panneau à coins arrondis posé PAR-DESSUS un visuel pleine largeur. Le
 * visuel est FIXÉ au bas de la fenêtre et peint derrière la page : il ne bouge
 * pas, c'est le panneau qui remonte et le découvre, comme un volet.
 *
 * Aucun JavaScript — l'effet est une pure géométrie de peinture. Voir
 * assets/styles/partials/footer.scss, qui porte le raisonnement complet.
 *
 * Si l'utilisateur demande à réduire les animations, le visuel défile avec la
 * page : un fond qui ne suit pas le contenu est un effet de parallaxe. Il reste
 * visible, rien ne devient inaccessible.
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

// La bande de l'application vit avec le pied de page et non dans les sections
// de l'accueil : elle lui appartient, et elle doit donc suivre toutes les
// pages qui portent ce pied de page.
$application = lcds_option('application');
$application = is_array($application) ? $application : [];
$appLink = is_array($application['lien'] ?? null) ? $application['lien'] : [];

$focus = LcdsFocalPoint::fromValue(lcds_option('cadrage'), LcdsFocalPoint::Center);
$visual = $reveal === 0 ? '' : lcds_render_image($reveal, [
    'class' => 'footer-reveal__image is-focus-' . $focus->value,
], 'full');
?>

<?php get_template_part('components/block-app', null, [
    'label' => trim((string) ($application['etiquette'] ?? '')),
    'dot' => trim((string) ($application['puce'] ?? '')) ?: 'orange',
    'title' => trim((string) ($application['titre'] ?? '')),
    'image' => lcds_attachment_id($application['visuel'] ?? 0),
    'code' => lcds_attachment_id($application['code'] ?? 0),
    'url' => trim((string) ($appLink['url'] ?? '')),
]); ?>

<div class="footer-reveal">
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
