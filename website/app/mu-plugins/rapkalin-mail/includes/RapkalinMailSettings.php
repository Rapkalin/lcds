<?php

/**
 * Settings contract of the mail transport: form normalisation, precedence
 * between the .env file and the admin, and password encryption.
 *
 * Pure on purpose — no WordPress call — so the suite can assert it outside
 * WordPress. The callers sanitise the raw request and supply the secret.
 *
 * @package rapkalin-mail
 */

if (! defined('ABSPATH')) {
    exit;
}

final class RapkalinMailSettings
{
    public const OPTION = 'rapkalin_mail_settings';

    public const TRANSPORT_SENDMAIL = 'sendmail';
    public const TRANSPORT_SMTP = 'smtp';
    public const TRANSPORTS = [self::TRANSPORT_SENDMAIL, self::TRANSPORT_SMTP];

    public const ENCRYPTIONS = ['', 'tls', 'ssl'];

    public const DEFAULT_SMTP_PORT = 587;

    public static function defaults(): array
    {
        return [
            'transport' => self::TRANSPORT_SENDMAIL,
            'smtp_host' => '',
            'smtp_port' => self::DEFAULT_SMTP_PORT,
            'smtp_encryption' => 'tls',
            'smtp_username' => '',
            'smtp_password' => '',
            'from_email' => '',
            'from_name' => '',
        ];
    }

    /**
     * Turns a sanitised form submission into the settings to store.
     *
     * The password is write-only: an empty field keeps the stored ciphertext,
     * and an empty username drops it, since it can no longer be used.
     */
    public static function normalize(array $input, array $stored, string $secret): array
    {
        $stored = array_merge(self::defaults(), $stored);
        $transport = (string) ($input['transport'] ?? '');
        $encryption = (string) ($input['smtp_encryption'] ?? '');
        $port = (int) ($input['smtp_port'] ?? 0);
        $username = trim((string) ($input['smtp_username'] ?? ''));
        $password = (string) ($input['smtp_password'] ?? '');
        $fromEmail = trim((string) ($input['from_email'] ?? ''));

        $storedPassword = $password === ''
            ? (string) $stored['smtp_password']
            : self::encrypt($password, $secret);

        return [
            'transport' => in_array($transport, self::TRANSPORTS, true) ? $transport : self::TRANSPORT_SENDMAIL,
            'smtp_host' => trim((string) ($input['smtp_host'] ?? '')),
            'smtp_port' => $port >= 1 && $port <= 65535 ? $port : self::DEFAULT_SMTP_PORT,
            'smtp_encryption' => in_array($encryption, self::ENCRYPTIONS, true) ? $encryption : '',
            'smtp_username' => $username,
            'smtp_password' => $username === '' ? '' : $storedPassword,
            'from_email' => filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false ? '' : $fromEmail,
            'from_name' => trim((string) ($input['from_name'] ?? '')),
        ];
    }

    /**
     * The transport actually used. SMTP_HOST in the .env file wins over the
     * admin, so a production database imported locally keeps sending to
     * Mailpit instead of real inboxes.
     *
     * @param array $env The SMTP_* and MAIL_FROM* values of the .env file.
     */
    public static function resolve(array $stored, array $env, string $secret): array
    {
        $envHost = trim((string) ($env['SMTP_HOST'] ?? ''));

        if ($envHost !== '') {
            $hasAuth = (bool) ($env['SMTP_AUTH'] ?? false);
            $encryption = (string) ($env['SMTP_SECURE'] ?? '');

            return [
                'source' => 'env',
                'transport' => self::TRANSPORT_SMTP,
                'smtp_host' => $envHost,
                'smtp_port' => (int) ($env['SMTP_PORT'] ?? 0) ?: self::DEFAULT_SMTP_PORT,
                'smtp_encryption' => in_array($encryption, self::ENCRYPTIONS, true) ? $encryption : '',
                'smtp_username' => $hasAuth ? (string) ($env['SMTP_USERNAME'] ?? '') : '',
                'smtp_password' => $hasAuth ? (string) ($env['SMTP_PASSWORD'] ?? '') : '',
                'is_password_unreadable' => false,
                'from_email' => (string) ($env['MAIL_FROM'] ?? ''),
                'from_name' => (string) ($env['MAIL_FROM_NAME'] ?? ''),
            ];
        }

        $settings = array_merge(self::defaults(), $stored);
        $cipher = (string) $settings['smtp_password'];
        $password = $cipher === '' ? '' : self::decrypt($cipher, $secret);

        return [
            'source' => 'settings',
            'transport' => in_array($settings['transport'], self::TRANSPORTS, true)
                ? $settings['transport']
                : self::TRANSPORT_SENDMAIL,
            'smtp_host' => (string) $settings['smtp_host'],
            'smtp_port' => (int) $settings['smtp_port'],
            'smtp_encryption' => (string) $settings['smtp_encryption'],
            'smtp_username' => (string) $settings['smtp_username'],
            'smtp_password' => $password ?? '',
            'is_password_unreadable' => $password === null,
            'from_email' => (string) $settings['from_email'] ?: (string) ($env['MAIL_FROM'] ?? ''),
            'from_name' => (string) $settings['from_name'] ?: (string) ($env['MAIL_FROM_NAME'] ?? ''),
        ];
    }

    public static function encrypt(string $plain, string $secret): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, self::key($secret)));
    }

    /**
     * Null when the value cannot be read back — typically after the WordPress
     * salts were rotated, which changes the key.
     */
    public static function decrypt(string $cipher, string $secret): ?string
    {
        $raw = base64_decode($cipher, true);

        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }

        $plain = sodium_crypto_secretbox_open(
            substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            self::key($secret),
        );

        return $plain === false ? null : $plain;
    }

    private static function key(string $secret): string
    {
        return sodium_crypto_generichash($secret, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
