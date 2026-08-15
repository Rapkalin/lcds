<?php

/**
 * The cache module's core contract: an entry can never exist without a TTL and
 * an invalidation group. Both are `match` expressions with no default arm, so a
 * forgotten case raises \UnhandledMatchError — this suite is what turns that
 * fail-fast into a CI failure instead of a production one.
 */

require_once dirname(__DIR__, 2) . '/website/app/mu-plugins/enums/LcdsCacheGroup.php';
require_once dirname(__DIR__, 2) . '/website/app/mu-plugins/enums/LcdsCacheKey.php';

it('gives every cache key a strictly positive TTL', function (LcdsCacheKey $key) {
    expect($key->ttl())->toBeGreaterThan(0);
})->with(LcdsCacheKey::cases());

it('gives every cache key an invalidation group', function (LcdsCacheKey $key) {
    expect($key->group())->toBeInstanceOf(LcdsCacheGroup::class);
})->with(LcdsCacheKey::cases());

it('keeps cache key names usable as transient names', function (LcdsCacheKey $key) {
    // sanitize_key() is unavailable outside WordPress; this mirrors the subset
    // the engine relies on when it decides not to hash the key.
    expect($key->value)->toMatch('/^[a-z0-9_]{1,60}$/');
})->with(LcdsCacheKey::cases());
