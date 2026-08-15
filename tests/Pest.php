<?php

/**
 * Pest bootstrap.
 *
 * The suite runs OUTSIDE WordPress: no database, no wp-load. Files under test
 * that guard themselves with `defined('ABSPATH')` need that constant, and the
 * cache enums use WordPress' time constants, so both are stubbed here.
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
