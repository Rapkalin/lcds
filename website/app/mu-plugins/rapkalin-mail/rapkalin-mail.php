<?php

/**
 * Plugin Name:  Rapkalin Mail
 * Description:  Outgoing email through the server's sendmail binary or an SMTP relay, configured in Settings → Email, with a test email button.
 * Version:      1.0.0
 * Author:       Rapkalin
 * Text Domain:  rapkalin-mail
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/RapkalinMailSettings.php';
require_once __DIR__ . '/includes/transport.php';
require_once __DIR__ . '/includes/settings-page.php';
