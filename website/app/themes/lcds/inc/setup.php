<?php

/**
 * Arguments d'un menu de navigation.
 *
 * `wp_nav_menu` produit lui-même le `<nav>` : écrire le conteneur à la main
 * imposerait `container => false`, refusé par le stub WordPress. `fallback_cb`
 * à false évite d'afficher la liste des pages quand aucun menu n'est assigné.
 */
function lcds_nav_menu_args(string $location, string $class, string $label): array
{
    return [
        'theme_location' => $location,
        'menu_id' => $location,
        'container' => 'nav',
        'container_class' => $class,
        'container_aria_label' => $label,
        'fallback_cb' => false,
    ];
}

/**
 * Set up the LCDS theme
 **/
if (!function_exists('theme_lcds_setup')) {
    function theme_lcds_setup(): void
    {
        add_theme_support('title-tag');
        add_theme_support('menus');
        add_theme_support('block-templates');
        register_nav_menus([
            'header-menu' => esc_html__('Header Menu', 'lcds'),
            'footer-menu' => esc_html__('Footer Menu', 'lcds'),
            'social-menu' => esc_html__('Social Media Menu', 'lcds'),
            'legal-menu' => esc_html__('Legal Menu', 'lcds'),
        ]);

        add_filter('use_block_editor_for_post', 'desactivate_gutemberg_pages', 10, 2);
        show_admin_bar(false);
    }
    add_action('after_setup_theme', 'theme_lcds_setup');
}

if (!function_exists('desactivate_gutemberg_pages')) {
    function desactivate_gutemberg_pages(bool $use_block_editor, \WP_Post $post): bool
    {
        return false;
    }
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
 * Enqueue scripts and styles.
 **/
if (!function_exists('theme_lcds_scripts')) {
    function theme_lcds_scripts(): void
    {
        wp_enqueue_style('lcds', get_stylesheet_directory_uri() . '/dist/main.css');
        wp_deregister_script('jquery');
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('wc-block-style'); // Remove WoocCommerce block css
        wp_dequeue_style('global-styles'); // Remove theme.json
        wp_dequeue_style('wp-emoji-styles'); // Remove emoji style
        wp_enqueue_script('main-js', get_template_directory_uri() . '/dist/main.js', [], '1.0', [
            'strategy' => 'defer',
            'in_footer' => true,
        ]);
    }
    add_action('wp_enqueue_scripts', 'theme_lcds_scripts');
}
