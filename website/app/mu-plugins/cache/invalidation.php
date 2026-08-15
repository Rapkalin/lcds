<?php

/**
 * LCDS — Application cache: invalidation actions.
 *
 * The WordPress hooks that decide WHICH events bump WHICH cache group. The cache
 * engine lives in cache/engine.php and the enums in enums/; lcds-cache.php loads
 * all of them via require_once.
 *
 * Defaults suit an editorial site and are adjustable via the
 * `lcds_cache_content_post_types` filter.
 *
 * NOTE: this file has no "Plugin Name" header on purpose, so the Bedrock
 * autoloader ignores it; it is loaded explicitly by lcds-cache.php.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Post types whose changes invalidate the "content" group.
 * Filterable to narrow/extend per project (custom post types, etc.).
 *
 * @return array<int, string>
 */
function lcds_cache_content_post_types(): array
{
    return apply_filters('lcds_cache_content_post_types', ['post', 'page']);
}

/**
 * Invalidates "content" when a relevant piece of content is saved.
 *
 * We skip the noise (autosaves, revisions, auto-drafts) and only react to the
 * configured public post types, so the cache is not busted on every keystroke.
 */
function lcds_cache_on_save_post(int $post_id, \WP_Post $post): void
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || $post->post_status === 'auto-draft') {
        return;
    }

    if (! in_array($post->post_type, lcds_cache_content_post_types(), true)) {
        return;
    }

    lcds_cache_flush_group(LcdsCacheGroup::Content);
}
add_action('save_post', 'lcds_cache_on_save_post', 10, 2);

/**
 * Invalidates "content" when a piece of content is deleted, trashed or restored.
 * These events are rare: we invalidate without filtering the post type (safe
 * over-invalidation) rather than risk serving a stale fragment.
 */
function lcds_cache_on_post_removed(): void
{
    lcds_cache_flush_group(LcdsCacheGroup::Content);
}
add_action('deleted_post', 'lcds_cache_on_post_removed');
add_action('trashed_post', 'lcds_cache_on_post_removed');
add_action('untrashed_post', 'lcds_cache_on_post_removed');

/**
 * Invalidates "content" when terms (categories, tags, taxonomies) change:
 * useful for "list by category" fragments.
 */
function lcds_cache_on_term_change(): void
{
    lcds_cache_flush_group(LcdsCacheGroup::Content);
}
add_action('created_term', 'lcds_cache_on_term_change');
add_action('edited_term', 'lcds_cache_on_term_change');
add_action('delete_term', 'lcds_cache_on_term_change');

/**
 * Invalidates "menus" when a navigation menu is updated.
 */
function lcds_cache_on_nav_menu_update(): void
{
    lcds_cache_flush_group(LcdsCacheGroup::Menus);
}
add_action('wp_update_nav_menu', 'lcds_cache_on_nav_menu_update');

// Theme switch: invalidate the whole application cache.
add_action('switch_theme', 'lcds_cache_flush_all');
