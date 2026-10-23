<?php

/**
 * LCDS — keeps third-party PHP deprecations off the rendered page.
 *
 * Yoast SEO still uses syntax that PHP 8.5 deprecates. With WP_DEBUG_DISPLAY on,
 * the notices are printed inside the HTML, which breaks the layout and the front
 * QA campaign. Engine deprecations raised outside the project's own code go to
 * the error log instead; ours stay displayed.
 *
 * E_USER_DEPRECATED is left alone on purpose: WordPress raises it from
 * wp-includes/functions.php even when the deprecated call is in our code.
 *
 * Temporary — see readme/docker.md for when to remove it.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! WP_DEBUG_DISPLAY) {
    return;
}

set_error_handler(
    static function (int $level, string $message, string $file, int $line): bool {
        foreach ([WPMU_PLUGIN_DIR, WP_CONTENT_DIR . '/themes'] as $ownCodeRoot) {
            if (str_starts_with($file, $ownCodeRoot . '/')) {
                return false;
            }
        }

        error_log(sprintf('PHP Deprecated:  %s in %s on line %d', $message, $file, $line));

        return true;
    },
    E_DEPRECATED,
);
