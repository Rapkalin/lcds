<?php

/**
 * LCDS — Application cache: engine.
 *
 * A "get-or-set" helper on top of WordPress' Transients API, used to cache data
 * that is expensive to compute (heavy queries, API calls, etc.) WITHOUT a
 * persistent object cache (Redis/Memcached). Transients therefore live in the
 * `wp_options` table.
 *
 * USAGE:
 *     $menu = lcds_cache_remember(LcdsCacheKey::HeaderMenu, function () {
 *         return build_menu();
 *     });
 *
 * CONVENTIONS (see readme/cache.md):
 *  - TTL > 0 is MANDATORY (a transient without expiration would be autoloaded).
 *  - Cache SCALARS / ARRAYS, not `WP_Post` / `WP_Query` objects.
 *  - The cache is OPPORTUNISTIC: the value may vanish at any time, so the
 *    callback must always be able to recompute it.
 *  - No re-entrant key (a callback must not re-`remember` its own key).
 *
 * INVALIDATION: by KEY VERSIONING (see `lcds_cache_key`). We do not delete
 * transients one by one; we bump a version number so stale keys are never read
 * again and expire on their own (through their TTL). The hooks that trigger the
 * bumps live in cache/invalidation.php.
 *
 * Loaded by lcds-cache.php (which also loads the LcdsCacheGroup enum).
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Name of the (autoloaded) option storing the cache versions.
 */
const LCDS_CACHE_VERSIONS_OPTION = 'lcds_cache_versions';

/**
 * Returns the cache versions map, initializing it when needed.
 *
 * The option is intentionally AUTOLOADED (`autoload = true`): it is read on every
 * `remember()` call, which avoids a dedicated SQL query.
 *
 * @return array<string, int>
 */
function lcds_cache_versions(): array
{
    $versions = get_option(LCDS_CACHE_VERSIONS_OPTION);

    if (! is_array($versions)) {
        $versions = ['__global__' => 1];
        add_option(LCDS_CACHE_VERSIONS_OPTION, $versions, '', true);
    }

    return $versions;
}

/**
 * Effective version token for a group: "{globalVersion}.{groupVersion}".
 */
function lcds_cache_version(LcdsCacheGroup $group): string
{
    $versions = lcds_cache_versions();
    $global = isset($versions['__global__']) ? (int) $versions['__global__'] : 1;
    $group_version = isset($versions[$group->value]) ? (int) $versions[$group->value] : 1;

    return $global . '.' . $group_version;
}

/**
 * Builds the transient name: versioned and kept under the 172-char limit.
 *
 * @param  string         $key   Readable business key (e.g. "header_menu").
 * @param  LcdsCacheGroup $group Invalidation group.
 */
function lcds_cache_key(string $key, LcdsCacheGroup $group): string
{
    $version = lcds_cache_version($group);

    // Keep the key readable when it is short and already "clean", otherwise hash
    // it (md5 is non-cryptographic here, only used to stay under the length limit).
    $slug = (strlen($key) <= 60 && sanitize_key($key) === $key) ? $key : md5($key);

    return "lcds_{$group->value}.{$version}_{$slug}";
}

/**
 * Returns a cached value, or computes it, stores it and returns it.
 *
 * The TTL and invalidation group are read from the LcdsCacheKey enum, so a call
 * site only picks the entry and provides the callback. For parameterized
 * entries (per post, per device…), pass a $suffix appended to the key name.
 *
 * @param  LcdsCacheKey $key      Cache entry (carries its own TTL + group).
 * @param  callable     $callback Callback producing the value on a miss.
 * @param  string       $suffix   Optional suffix for parameterized keys.
 */
function lcds_cache_remember(LcdsCacheKey $key, callable $callback, string $suffix = ''): mixed
{
    $name = $suffix === '' ? $key->value : $key->value . '_' . $suffix;
    // TTL > 0 is mandatory: a transient with a 0 TTL would be stored as autoload.
    $ttl = max(1, $key->ttl());
    $transient = lcds_cache_key($name, $key->group());

    $cached = get_transient($transient);

    // Sentinel: distinguishes "absent" from a legitimate false / null / 0 value
    // (otherwise those values would never be cached).
    if (is_array($cached) && array_key_exists('v', $cached)) {
        return $cached['v'];
    }

    $value = lcds_cache_compute($callback, $transient, $ttl);
    set_transient($transient, ['v' => $value], $ttl);

    return $value;
}

if (! function_exists('lcds_cache_compute')) {
    /**
     * Runs the expensive callback. ANTI-STAMPEDE (dogpile) EXTENSION POINT.
     *
     * The default implementation simply runs the callback and DELIBERATELY
     * ignores $transient and $ttl. They are still part of the signature on
     * purpose: an override that adds stampede protection needs $transient as the
     * lock key and $ttl to size the lock / stale-while-revalidate window —
     * without having to change any call site. To enable it, redefine this
     * function in a mu-plugin loaded BEFORE this one (a filename alphabetically
     * before "lcds-cache.php", or a Composer package).
     *
     * @param  string $transient Full transient name (usable as a lock key).
     */
    function lcds_cache_compute(callable $callback, string $transient, int $ttl): mixed
    {
        return call_user_func($callback);
    }
}

/**
 * Invalidates a group: bumps its version so its keys become stale.
 */
function lcds_cache_flush_group(LcdsCacheGroup $group): void
{
    $versions = lcds_cache_versions();
    $versions[$group->value] = (isset($versions[$group->value]) ? (int) $versions[$group->value] : 1) + 1;
    update_option(LCDS_CACHE_VERSIONS_OPTION, $versions);
}

/**
 * Invalidates the WHOLE application cache: bumps the global version.
 */
function lcds_cache_flush_all(): void
{
    $versions = lcds_cache_versions();
    $versions['__global__'] = (isset($versions['__global__']) ? (int) $versions['__global__'] : 1) + 1;
    update_option(LCDS_CACHE_VERSIONS_OPTION, $versions);
}
