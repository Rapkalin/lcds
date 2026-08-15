<?php

/**
 * En-tête du site.
 *
 * Les menus laissent `wp_nav_menu` produire son propre conteneur `<nav>` :
 * l'écrire à la main obligerait à passer `container => false`, que le stub
 * WordPress refuse, et Pint désaligne les appels multi-lignes noyés dans du HTML.
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
    <link rel="shortcut icon" href="<?php echo esc_url(get_stylesheet_directory_uri() . '/favicon.ico'); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main-content"><?php esc_html_e('Aller au contenu', 'lcds'); ?></a>

<header id="site-header" class="site-header">
    <div class="site-branding">
        <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>
    </div>

    <?php wp_nav_menu(lcds_nav_menu_args('header-menu', 'site-navigation', __('Navigation principale', 'lcds'))); ?>
</header>
