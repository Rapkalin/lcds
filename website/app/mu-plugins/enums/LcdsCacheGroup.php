<?php

/**
 * Cache invalidation groups — single source of truth for every cache group.
 *
 * To add a group, add a case here, then wire its invalidation in
 * cache/invalidation.php (only Content/Menus are wired by default). The backing
 * string is used as the group key in the stored versions map and in transient
 * names, so keep values stable once the site is live.
 *
 * NOTE: this file has no "Plugin Name" header on purpose, so the Bedrock
 * autoloader ignores it; it is loaded explicitly by lcds-cache.php.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

enum LcdsCacheGroup: string
{
    // Catch-all group; only cleared by lcds_cache_flush_all() or its TTL.
    case Default = 'default';

    // Editorial content (posts, pages, terms). Auto-invalidated.
    case Content = 'content';

    // Navigation menus. Auto-invalidated on wp_update_nav_menu.
    case Menus = 'menus';
}
