<?php

/**
 * En-tête du site.
 *
 * Volontairement réduit au nom du site : pas de navigation tant que
 * l'arborescence du projet n'est pas arrêtée. Les emplacements de menu restent
 * déclarés dans inc/setup.php, prêts à être rendus ici.
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
    <div class="site-branding">
        <a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>
    </div>
</header>
