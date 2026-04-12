<?php

/**
* Return images folder path
*/
function asset($path): string
{
    return get_template_directory_uri() . '/assets/images/' . $path;
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

function build_menu(): array
{
    /*
     * If menu is cached, we return the cached version
     * All transient keys are prefixed with lcds_
     */
    // $transientKey = get_transient_key('menu_items');
    //if ($cachedData = get_transient($transientKey)) {
    //    return $cachedData;
    //}

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
            'is_contact' => false
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
        if((int) $item->menu_item_parent && isset($menu[$item->menu_item_parent])) {
            $menu[$item->menu_item_parent]['children'][$menuItem['title']] = $menuItem;
            $menu[$item->menu_item_parent]['type'] = 'pages';
        } else {
            $menu[$item->ID] = $menuItem;
        }
    }

    // Set the contact block for the menu
    set_menu_entry_contact($menu);

    // Cache for 1 hour
   // set_transient($transientKey, $menu, HOUR_IN_SECONDS);

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
            $block['block_id']['label'] &&
            $block['block_id']['identifier']
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

function get_transient_key(string $key): mixed
{
    // Create transient key
    return CACHE_PREFIX .$key . md5(serialize([
            'locale' => get_locale(),
            'user_role' => wp_get_current_user()->roles[0] ?? 'guest',
    ]));
}

function isMobileOrTablet() : bool
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    return preg_match(
            '/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i',
            $userAgent
        ) === 1
    ;
}
