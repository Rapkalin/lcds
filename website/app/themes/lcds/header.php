<?php

/**
 * En-tête du site.
 *
 * La navigation et le bouton d'action viennent chacun de leur propre
 * emplacement de menu — voir inc/navigation.php.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main-content"><?php esc_html_e('Aller au contenu', 'lcds'); ?></a>

<header id="site-header" class="site-header">
    <a class="site-header__logo" href="<?php echo esc_url(home_url('/')); ?>" rel="home">
        <?php get_template_part('components/site-logo'); ?>
        <span class="screen-reader-text"><?php echo esc_html(get_bloginfo('name')); ?></span>
    </a>

    <button class="site-header__toggle" type="button" aria-expanded="false" aria-controls="site-header-nav">
        <span class="site-header__toggle-bars" aria-hidden="true"></span>
        <span class="screen-reader-text"><?php esc_html_e('Menu', 'lcds'); ?></span>
    </button>

    <div id="site-header-nav" class="site-header__nav">
        <?php lcds_header_nav(); ?>
        <?php lcds_header_cta(); ?>
    </div>
</header>
