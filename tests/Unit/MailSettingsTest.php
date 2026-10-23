<?php

/**
 * The mail transport's settings contract.
 *
 * Three decisions are guarded here: SMTP_HOST in the .env file wins over the
 * admin (a production database imported locally must never reach real
 * inboxes), the SMTP password is write-only, and it is never stored in clear.
 */

require_once dirname(__DIR__, 2) . '/website/app/mu-plugins/rapkalin-mail/includes/RapkalinMailSettings.php';

const SECRET = 'auth-key-and-salt';

function mailEnv(array $overrides = []): array
{
    return array_merge([
        'SMTP_HOST' => '',
        'SMTP_PORT' => 0,
        'SMTP_AUTH' => false,
        'SMTP_SECURE' => '',
        'SMTP_USERNAME' => '',
        'SMTP_PASSWORD' => '',
        'MAIL_FROM' => 'env@example.com',
        'MAIL_FROM_NAME' => 'Env sender',
    ], $overrides);
}

function smtpForm(array $overrides = []): array
{
    return array_merge([
        'transport' => 'smtp',
        'smtp_host' => 'smtp.example.com',
        'smtp_port' => 465,
        'smtp_encryption' => 'ssl',
        'smtp_username' => 'user@example.com',
        'smtp_password' => 's3cret',
        'from_email' => 'admin@example.com',
        'from_name' => 'Admin sender',
    ], $overrides);
}

it('sends through the server sendmail binary when nothing is configured', function () {
    $config = RapkalinMailSettings::resolve([], mailEnv(), SECRET);

    expect($config['source'])->toBe('settings');
    expect($config['transport'])->toBe('sendmail');
});

it('lets SMTP_HOST in the .env file override the admin settings', function () {
    $stored = RapkalinMailSettings::normalize(smtpForm(), [], SECRET);
    $config = RapkalinMailSettings::resolve($stored, mailEnv(['SMTP_HOST' => 'mailpit', 'SMTP_PORT' => 1025]), SECRET);

    expect($config['source'])->toBe('env');
    expect($config['transport'])->toBe('smtp');
    expect($config['smtp_host'])->toBe('mailpit');
    expect($config['smtp_port'])->toBe(1025);
    expect($config['smtp_encryption'])->toBe('');
    expect($config['smtp_username'])->toBe('');
    expect($config['from_email'])->toBe('env@example.com');
});

it('only uses the .env credentials when SMTP_AUTH is on', function (bool $hasAuth, string $expected) {
    $config = RapkalinMailSettings::resolve([], mailEnv([
        'SMTP_HOST' => 'smtp.example.com',
        'SMTP_AUTH' => $hasAuth,
        'SMTP_USERNAME' => 'user',
        'SMTP_PASSWORD' => 'pass',
    ]), SECRET);

    expect($config['smtp_username'])->toBe($expected);
})->with([[true, 'user'], [false, '']]);

it('uses the admin settings when the .env file leaves SMTP_HOST empty', function () {
    $stored = RapkalinMailSettings::normalize(smtpForm(), [], SECRET);
    $config = RapkalinMailSettings::resolve($stored, mailEnv(), SECRET);

    expect($config['source'])->toBe('settings');
    expect($config['transport'])->toBe('smtp');
    expect($config['smtp_host'])->toBe('smtp.example.com');
    expect($config['smtp_port'])->toBe(465);
    expect($config['smtp_password'])->toBe('s3cret');
    expect($config['from_email'])->toBe('admin@example.com');
    expect($config['from_name'])->toBe('Admin sender');
});

it('falls back to the .env sender when the admin leaves it empty', function () {
    $stored = RapkalinMailSettings::normalize(smtpForm(['from_email' => '', 'from_name' => '']), [], SECRET);
    $config = RapkalinMailSettings::resolve($stored, mailEnv(), SECRET);

    expect($config['from_email'])->toBe('env@example.com');
    expect($config['from_name'])->toBe('Env sender');
});

it('never stores the password in clear', function () {
    $stored = RapkalinMailSettings::normalize(smtpForm(), [], SECRET);

    expect($stored['smtp_password'])->not->toBe('')->not->toContain('s3cret');
});

it('keeps the stored password when the field is left empty', function () {
    $first = RapkalinMailSettings::normalize(smtpForm(), [], SECRET);
    $second = RapkalinMailSettings::normalize(smtpForm(['smtp_password' => '']), $first, SECRET);

    expect($second['smtp_password'])->toBe($first['smtp_password']);
});

it('replaces the stored password when a new one is typed', function () {
    $first = RapkalinMailSettings::normalize(smtpForm(), [], SECRET);
    $second = RapkalinMailSettings::normalize(smtpForm(['smtp_password' => 'n3w']), $first, SECRET);

    expect(RapkalinMailSettings::decrypt($second['smtp_password'], SECRET))->toBe('n3w');
});

it('drops the password once the username is removed', function () {
    $first = RapkalinMailSettings::normalize(smtpForm(), [], SECRET);
    $second = RapkalinMailSettings::normalize(smtpForm(['smtp_username' => '', 'smtp_password' => '']), $first, SECRET);

    expect($second['smtp_password'])->toBe('');
});

it('flags a password it can no longer decrypt instead of sending a wrong one', function () {
    $stored = RapkalinMailSettings::normalize(smtpForm(), [], SECRET);
    $config = RapkalinMailSettings::resolve($stored, mailEnv(), 'rotated-salts');

    expect($config['smtp_password'])->toBe('');
    expect($config['is_password_unreadable'])->toBeTrue();
});

it('rejects a corrupted ciphertext', function (string $cipher) {
    expect(RapkalinMailSettings::decrypt($cipher, SECRET))->toBeNull();
})->with(['', 'not base64 !', 'c2hvcnQ=']);

it('falls back to safe values on unexpected input', function () {
    $stored = RapkalinMailSettings::normalize(smtpForm([
        'transport' => 'mail',
        'smtp_port' => 70000,
        'smtp_encryption' => 'starttls',
        'from_email' => 'not an address',
    ]), [], SECRET);

    expect($stored['transport'])->toBe('sendmail');
    expect($stored['smtp_port'])->toBe(RapkalinMailSettings::DEFAULT_SMTP_PORT);
    expect($stored['smtp_encryption'])->toBe('');
    expect($stored['from_email'])->toBe('');
});
