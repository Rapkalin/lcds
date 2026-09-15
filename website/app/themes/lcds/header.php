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

/**
 * Le logo animé au défilement ne vit QUE sur la page d'accueil.
 *
 * L'attribut `data-logo-scroll` est le seul point d'accroche du script : sans
 * lui il ne cherche rien, et l'en-tête des autres pages garde la marque à sa
 * taille de maquette, sans partie écrite. Le gabarit décide, pas le script.
 *
 * `is_front_page()` plutôt qu'une classe de `body` : la classe dépend des
 * réglages de lecture, l'appel répond à la question posée.
 */
$lcds_logo_anime = is_front_page();
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
        <span class="site-logo-lockup"<?php echo $lcds_logo_anime ? ' data-logo-scroll' : ''; ?>>
            <?php get_template_part('components/site-logo'); ?>
            <?php if ($lcds_logo_anime) : ?>
                <?php get_template_part('components/site-logo-word'); ?>
            <?php endif; ?>
        </span>
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
