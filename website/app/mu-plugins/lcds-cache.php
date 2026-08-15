<?php

/**
 * LCDS — Application cache (mu-plugin entry point / index).
 *
 * Caching is INFRASTRUCTURE, so it lives in a mu-plugin: always loaded (WP-CLI,
 * cron, admin), survives a theme switch, and reusable by other code. This file
 * is only an INDEX — it `require_once`s the module's parts. To extend the
 * module, add a file under cache/ (or enums/) and require it here.
 *
 * Module files:
 *  - enums/LcdsCacheGroup.php → cache groups (single source of truth);
 *  - enums/LcdsCacheKey.php   → cache keys, each carrying its TTL + group;
 *  - cache/engine.php         → the engine (get-or-set, versioning, flush);
 *  - cache/invalidation.php   → WordPress hooks that bust the cache.
 *
 * See readme/cache.md for usage and conventions.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

// Plain includes (no "Plugin Name" header) so the Bedrock autoloader ignores
// them; they load only from here. Order: enums first (LcdsCacheKey uses
// LcdsCacheGroup), then the engine, then the hooks that use them (add_action
// only registers callbacks, which run later).
require_once __DIR__ . '/enums/LcdsCacheGroup.php';
require_once __DIR__ . '/enums/LcdsCacheKey.php';
require_once __DIR__ . '/cache/engine.php';
require_once __DIR__ . '/cache/invalidation.php';
