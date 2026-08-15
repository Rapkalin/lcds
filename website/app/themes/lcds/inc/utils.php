<?php

/**
* Return images folder path
*/
function asset(string $path): string
{
    return get_template_directory_uri() . '/assets/images/' . $path;
}

/**
 * Addresses attached to the "contact" entry of the mobile menu.
 *
 * header.php has always called this function, but it was never defined: every
 * render of a contact menu entry on mobile was a fatal error. The data source
 * (ACF options group) is not wired yet — returning an empty list keeps the
 * header rendering, and the filter is where the real addresses plug in.
 */
function get_addresses(): array
{
    return apply_filters('lcds_contact_addresses', []);
}

function get_terms_hierarchy(string $taxonomy): array
{
    $parent_terms = get_terms([
        'taxonomy' => $taxonomy,
        'parent' => 0,
    ]);

    $terms = [];
    foreach ($parent_terms as $parent_term) {
        $terms[$parent_term->name] = get_terms([
            'taxonomy' => $taxonomy,
            'child_of' => $parent_term->term_id,
        ]);
    }

    return $terms;
}

/**
 * Header navigation tree, served from the application cache.
 *
 * The tree differs between small and large screens (get_item_menu_children()
 * drops the taxonomy children on mobile/tablet), so the device variant is part
 * of the cache key — otherwise the first visitor would decide which menu every
 * other visitor gets.
 */
function get_header_menu(): array
{
    $variant = isMobileOrTablet() ? 'mobile' : 'desktop';

    return lcds_cache_remember(LcdsCacheKey::HeaderMenu, 'build_menu', $variant);
}

function build_menu(): array
{
    $menu = [];
    $items = wp_get_nav_menu_items('header-menu', [
        'theme_location' => 'header-menu',
        'menu_id' => 'header-menu',
    ]);

    foreach ($items as $item) {
        $menuItem = [
            'title' => $item->title,
            'url' => $item->url,
            'children' => [],
            'is_contact' => false,
        ];

        if ($item->object === 'page') {
            get_item_menu_children($item, $menuItem);
        }

        /*
         * If a menu entry has a parent then we bind it to its parent with type = pages
         * menu entry
         *  -> child one
         *  -> child two
         */
        if ((int) $item->menu_item_parent && isset($menu[$item->menu_item_parent])) {
            $menu[$item->menu_item_parent]['children'][$menuItem['title']] = $menuItem;
            $menu[$item->menu_item_parent]['type'] = 'pages';
        } else {
            $menu[$item->ID] = $menuItem;
        }
    }

    // Set the contact block for the menu
    set_menu_entry_contact($menu);

    return $menu;
}

function get_item_menu_children(WP_Post $item, array &$menuItem): void
{
    switch (get_page_template_slug((int) $item->object_id)):
        case 'page-x.php':
            if (!isMobileOrTablet()) {
                $menuItem['children'] = get_terms_hierarchy('lcds-xx');
                $menuItem['type'] = 'tags';
            }
            break;
        case 'page-y.php':
            $menuItem['children'] = get_page_block_ids((int) $item->object_id);
            $menuItem['type'] = 'anchors';
            break;
        case 'page-contact.php':
            $menuItem['children'] = [];
            $menuItem['type'] = 'links';
            $menuItem['is_contact'] = true;
            break;
    endswitch;
}

function get_page_block_ids(int $pageId): array
{
    $block_ids = [];
    $fields = get_fields($pageId);

    foreach ($fields['content_blocks'] as $block) {
        if (
            $block['block_id']['label']
            && $block['block_id']['identifier']
        ) {
            $block_ids[$block['block_id']['label']] = $block['block_id']['identifier'];
        }
    }

    return $block_ids;
}

function set_menu_entry_contact(array &$menuItems): void
{
    foreach ($menuItems as &$menuItem) {
        /*
         * If the entry is a direct page && a contact page this key is already set to true
         * Else we check if any of the children of the entry is a contact page type and if so, then we set the parent as contact as well
         */
        if (!$menuItem['is_contact']) {
            $menuItem['is_contact'] = check_menu_entry_contact($menuItem['children']);
        }
    }
}

function check_menu_entry_contact(array $childrenItem): bool
{
    /*
     * Is one of the child is contact we return true
     * even tho the other are false or not
     */
    foreach ($childrenItem as $child) {
        if (isset($child['is_contact']) && $child['is_contact']) {
            return true;
        }
    }

    return false;
}

function isMobileOrTablet(): bool
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    return preg_match(
        '/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i',
        $userAgent,
    ) === 1
    ;
}
