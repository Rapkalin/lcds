<?php

/**
 * Outgoing transport: points every wp_mail() at the resolved configuration.
 *
 * Mail goes through wp_mail() and the `phpmailer_init` hook rather than a
 * separately-required PHPMailer: WordPress ships its own copy of the
 * PHPMailer\PHPMailer\PHPMailer class and loads it with a bare require_once, so
 * instantiating a Composer copy of the same class in the same request is a fatal
 * redeclaration waiting to happen.
 *
 * @package rapkalin-mail
 */

use function Env\env;

if (! defined('ABSPATH')) {
    exit;
}

function rapkalin_mail_env(): array
{
    return [
        'SMTP_HOST' => (string) env('SMTP_HOST'),
        'SMTP_PORT' => (int) env('SMTP_PORT'),
        'SMTP_AUTH' => (bool) env('SMTP_AUTH'),
        'SMTP_SECURE' => (string) env('SMTP_SECURE'),
        'SMTP_USERNAME' => (string) env('SMTP_USERNAME'),
        'SMTP_PASSWORD' => (string) env('SMTP_PASSWORD'),
        'MAIL_FROM' => (string) env('MAIL_FROM'),
        'MAIL_FROM_NAME' => (string) env('MAIL_FROM_NAME'),
    ];
}

function rapkalin_mail_stored_settings(): array
{
    $stored = get_option(RapkalinMailSettings::OPTION, []);

    return is_array($stored) ? $stored : [];
}

function rapkalin_mail_config(): array
{
    return RapkalinMailSettings::resolve(rapkalin_mail_stored_settings(), rapkalin_mail_env(), wp_salt('auth'));
}

/**
 * This MUST go through the `wp_mail_from` filter and not through
 * `phpmailer_init`: wp_mail() calls setFrom() BEFORE firing phpmailer_init, so a
 * From set in that hook arrives too late. WordPress' own default is
 * `wordpress@{host}`, which PHPMailer rejects outright when the host carries no
 * dot (`localhost`, `localhost:8080`) — every send would fail.
 */
function rapkalin_mail_from(string $from): string
{
    $configured = rapkalin_mail_config()['from_email'];

    return is_email($configured) ? $configured : $from;
}
add_filter('wp_mail_from', 'rapkalin_mail_from');

function rapkalin_mail_from_name(string $name): string
{
    $configured = rapkalin_mail_config()['from_name'];

    return $configured !== '' ? $configured : $name;
}
add_filter('wp_mail_from_name', 'rapkalin_mail_from_name');

function rapkalin_mail_configure_transport(\PHPMailer\PHPMailer\PHPMailer $phpmailer): void
{
    $config = rapkalin_mail_config();
    $phpmailer->CharSet = 'UTF-8';

    if ($config['transport'] === RapkalinMailSettings::TRANSPORT_SMTP) {
        $phpmailer->isSMTP();
        $phpmailer->Host = $config['smtp_host'];
        $phpmailer->Port = $config['smtp_port'];
        $phpmailer->SMTPAuth = $config['smtp_username'] !== '';
        $phpmailer->Username = $config['smtp_username'];
        $phpmailer->Password = $config['smtp_password'];

        if ($config['smtp_encryption'] === '') {
            // Without this, PHPMailer attempts STARTTLS on its own and an
            // unencrypted relay such as Mailpit rejects every message.
            $phpmailer->SMTPSecure = '';
            $phpmailer->SMTPAutoTLS = false;
        } else {
            $phpmailer->SMTPSecure = $config['smtp_encryption'];
        }

        // Left empty, the SMTP envelope sender is not the From address and the
        // message fails SPF checks.
        if (filter_var($phpmailer->Sender, FILTER_VALIDATE_EMAIL) === false) {
            $phpmailer->Sender = $phpmailer->From;
        }
    } else {
        // mail() and not isSendmail(): both hand the message to the binary in
        // sendmail_path, but isSendmail() needs popen(), which shared hosts may
        // disable. mail() is also WordPress' default, so a server with nothing
        // configured keeps sending exactly as it did before this plugin.
        //
        // No envelope sender here: PHPMailer would pass it as `-f`, and the
        // IONOS relay rejects any address but its account's own
        // ("550 Sender address is not allowed"). Cleared, not just left alone:
        // WordPress reuses one PHPMailer per request and never resets it.
        $phpmailer->isMail();
        $phpmailer->Sender = '';
    }
}
add_action('phpmailer_init', 'rapkalin_mail_configure_transport');
