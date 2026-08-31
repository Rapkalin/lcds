<?php

/**
 * Pest bootstrap.
 *
 * The suite runs OUTSIDE WordPress: no database, no wp-load. Files under test
 * that guard themselves with `defined('ABSPATH')` need that constant, the cache
 * enums use WordPress' time constants, and the menu enum translates its labels
 * through `__()` — all three are stubbed here.
 */

if (! defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/website/wordpress-core/');
}

if (! defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
    define('HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS);
    define('DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS);
    define('WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS);
}

// The menu location enum builds its labels with __(). Returning the source
// string is enough: the suite asserts the contract, not the translation.
if (! function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}
