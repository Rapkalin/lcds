<?php

/**
 * LCDS theme bootstrap.
 *
 * The application cache lives in the rapkalin-cache mu-plugin, not here: it
 * must stay available to WP-CLI, cron and the admin.
 *
 * @package lcds
 */

require_once __DIR__ . '/inc/security.php';
require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/menus.php';
require_once __DIR__ . '/inc/navigation.php';
require_once __DIR__ . '/inc/template.php';
require_once __DIR__ . '/inc/images.php';
require_once __DIR__ . '/inc/acf.php';
require_once __DIR__ . '/inc/editor.php';
require_once __DIR__ . '/inc/options.php';
require_once __DIR__ . '/inc/roles.php';
require_once __DIR__ . '/inc/business.php';
require_once __DIR__ . '/inc/seo.php';
require_once __DIR__ . '/inc/contacts.php';
