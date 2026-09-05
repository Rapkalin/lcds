<?php

/**
 * Set up the LCDS theme
 **/
if (!function_exists('theme_lcds_setup')) {
    function theme_lcds_setup(): void
    {
        add_theme_support('title-tag');
        add_theme_support('menus');
        add_theme_support('block-templates');
        // Les emplacements de menu sont déclarés dans inc/menus.php, à partir
        // de l'enum LcdsMenuLocation.

        // L'éditeur de blocs est coupé PAGE PAR PAGE et non ici : voir
        // inc/editor.php. Les contenus contribués par un champ de contenu
        // flexible le perdent, le texte libre le garde.
        show_admin_bar(false);
    }
    add_action('after_setup_theme', 'theme_lcds_setup');
}

if (!function_exists('theme_lcds_title_separator')) {
    function theme_lcds_title_separator(): string
    {
        return '|';
    }
    // `document_title_separator` is a FILTER: registered as an action, the
    // returned separator was discarded and the default one kept applying.
    add_filter('document_title_separator', 'theme_lcds_title_separator');
}

/**
 * Reorganize the lcds admin menu
 **/
if (!function_exists('theme_remove_admin_menus')) {
    // Removes from admin menu
    function theme_remove_admin_menus(): void
    {
        remove_menu_page('edit-comments.php');
        remove_menu_page('edit.php');
    }
    add_action('admin_menu', 'theme_remove_admin_menus');
}

if (!function_exists('theme_remove_comment_support')) {
    // Removes from post and pages
    function theme_remove_comment_support(): void
    {
        remove_post_type_support('post', 'comments');
        remove_post_type_support('page', 'comments');
    }
    add_action('init', 'theme_remove_comment_support', 100);
}

if (!function_exists('theme_admin_bar_render')) {
    // Removes from admin bar
    function theme_admin_bar_render(): void
    {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('comments');
    }
    add_action('wp_before_admin_bar_render', 'theme_admin_bar_render');
}

if (!function_exists('theme_custom_menu_order')) {
    // Wired to both `custom_menu_order` (receives a bool) and `menu_order`
    // (receives the array of menu slugs), hence the union type.
    function theme_custom_menu_order(array|bool $menu_ord): array|bool
    {
        if (!$menu_ord) {
            return true;
        }

        // Les types de contenu propres au projet viendront s'insérer ici au fur
        // et à mesure de leur création.
        return [
            "index.php",
            "separator1",
            "edit.php?post_type=page",
            "upload.php",
            "separator2",
            "themes.php",
            "plugins.php",
            "users.php",
            "tools.php",
            "options-general.php",
            "edit.php?post_type=acf-field-group",
            "separator-last",
        ];
    }
    add_filter('custom_menu_order', 'theme_custom_menu_order');
    add_filter('menu_order', 'theme_custom_menu_order');
}

/**
 * Version d'un asset compilé, dérivée de sa date de modification.
 *
 * Indispensable : le .htaccess sert dist/ avec un `Expires` à un mois et les
 * fichiers produits par webpack gardent un nom fixe. Sans cette version, une
 * mise en production ne parvenait pas aux visiteurs déjà venus — WordPress
 * n'ajoutait que `?ver=` suivi de SA propre version, identique d'un déploiement
 * à l'autre.
 **/
if (!function_exists('theme_lcds_asset_version')) {
    function theme_lcds_asset_version(string $relative_path): string
    {
        $modified_at = filemtime(get_template_directory() . '/' . $relative_path);

        return $modified_at === false ? '1.0' : (string) $modified_at;
    }
}

/**
 * Enqueue scripts and styles.
 **/
if (!function_exists('theme_lcds_scripts')) {
    function theme_lcds_scripts(): void
    {
        wp_enqueue_style(
            'lcds',
            get_template_directory_uri() . '/dist/main.css',
            [],
            theme_lcds_asset_version('dist/main.css'),
        );
        wp_deregister_script('jquery');
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('wc-block-style'); // Remove WoocCommerce block css
        wp_dequeue_style('global-styles'); // Remove theme.json
        wp_dequeue_style('wp-emoji-styles'); // Remove emoji style
        wp_enqueue_script(
            'main-js',
            get_template_directory_uri() . '/dist/main.js',
            [],
            theme_lcds_asset_version('dist/main.js'),
            [
                'strategy' => 'defer',
                'in_footer' => true,
            ],
        );
    }
    add_action('wp_enqueue_scripts', 'theme_lcds_scripts');
}
