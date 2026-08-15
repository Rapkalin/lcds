<?php

/**
 * Cache keys — single source of truth for every named cache entry.
 *
 * Each case IS a cached entry: its backing string is the key name, and its
 * ttl() / group() methods declare how long it lives and which invalidation
 * group it belongs to. To register an entry, add a case AND its arm in BOTH
 * match() below — a missing arm raises UnhandledMatchError (fail-fast), so an
 * entry can never exist without a TTL and a group.
 *
 * Parameterized entries (per post, per user…): keep a single case for the
 * "family" and pass a $suffix to lcds_cache_remember() (e.g. the post ID).
 *
 * NOTE: this file has no "Plugin Name" header on purpose, so the Bedrock
 * autoloader ignores it; it is loaded explicitly by lcds-cache.php.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

enum LcdsCacheKey: string
{
    // Entrée d'exemple, sans consommateur : à remplacer par la première vraie
    // clé du projet. Conservée pour que le module reste démontré et couvert par
    // les tests tant qu'aucune donnée n'est mise en cache.
    case Example = 'example';

    /**
     * Time to live, in seconds, for this entry.
     */
    public function ttl(): int
    {
        return match ($this) {
            self::Example => HOUR_IN_SECONDS,
        };
    }

    /**
     * Invalidation group this entry belongs to.
     */
    public function group(): LcdsCacheGroup
    {
        return match ($this) {
            self::Example => LcdsCacheGroup::Content,
        };
    }
}
