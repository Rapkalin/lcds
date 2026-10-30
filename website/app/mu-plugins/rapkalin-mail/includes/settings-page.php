<?php

/**
 * Settings → Email: transport settings and a test email button.
 *
 * Both forms post to admin-post.php and redirect back, so reloading the page
 * never sends or saves twice. The result travels in a short-lived, per-user
 * transient.
 *
 * @package rapkalin-mail
 */

if (! defined('ABSPATH')) {
    exit;
}

const RAPKALIN_MAIL_PAGE = 'rapkalin-mail';
const RAPKALIN_MAIL_CAPABILITY = 'manage_options';
const RAPKALIN_MAIL_SAVE_ACTION = 'rapkalin_mail_save';
const RAPKALIN_MAIL_TEST_ACTION = 'rapkalin_mail_test';

add_action('admin_menu', static function (): void {
    $hookSuffix = add_options_page(
        __('Email', 'rapkalin-mail'),
        __('Email', 'rapkalin-mail'),
        RAPKALIN_MAIL_CAPABILITY,
        RAPKALIN_MAIL_PAGE,
        'rapkalin_mail_render_page',
    );

    if ($hookSuffix !== false) {
        add_action('admin_print_styles-' . $hookSuffix, 'rapkalin_mail_print_styles');
    }
});

add_action('admin_post_' . RAPKALIN_MAIL_SAVE_ACTION, 'rapkalin_mail_handle_save');
add_action('admin_post_' . RAPKALIN_MAIL_TEST_ACTION, 'rapkalin_mail_handle_test');

function rapkalin_mail_authorize(string $action): void
{
    if (! current_user_can(RAPKALIN_MAIL_CAPABILITY)) {
        wp_die(esc_html__('You are not allowed to manage email settings.', 'rapkalin-mail'), 403);
    }

    check_admin_referer($action);
}

function rapkalin_mail_notice_key(): string
{
    return 'rapkalin_mail_notice_' . get_current_user_id();
}

function rapkalin_mail_redirect_with_notice(bool $isSuccess, string $message): never
{
    set_transient(rapkalin_mail_notice_key(), ['is_success' => $isSuccess, 'message' => $message], MINUTE_IN_SECONDS);
    wp_safe_redirect(admin_url('options-general.php?page=' . RAPKALIN_MAIL_PAGE));
    exit;
}

function rapkalin_mail_handle_save(): void
{
    rapkalin_mail_authorize(RAPKALIN_MAIL_SAVE_ACTION);

    $field = static fn(string $name): string => sanitize_text_field(wp_unslash((string) ($_POST[$name] ?? '')));

    $input = [
        'transport' => $field('transport'),
        'smtp_host' => $field('smtp_host'),
        'smtp_port' => absint($_POST['smtp_port'] ?? 0),
        'smtp_encryption' => $field('smtp_encryption'),
        'smtp_username' => $field('smtp_username'),
        // Not sanitize_text_field(): it would strip characters a password may
        // legitimately contain. The value is encrypted, never output.
        'smtp_password' => wp_unslash((string) ($_POST['smtp_password'] ?? '')),
        'from_email' => sanitize_email(wp_unslash((string) ($_POST['from_email'] ?? ''))),
        'from_name' => $field('from_name'),
    ];

    $settings = RapkalinMailSettings::normalize($input, rapkalin_mail_stored_settings(), wp_salt('auth'));
    update_option(RapkalinMailSettings::OPTION, $settings, false);

    rapkalin_mail_redirect_with_notice(true, __('Settings saved.', 'rapkalin-mail'));
}

function rapkalin_mail_handle_test(): void
{
    rapkalin_mail_authorize(RAPKALIN_MAIL_TEST_ACTION);

    $recipient = sanitize_email(wp_unslash((string) ($_POST['recipient'] ?? '')));

    if (! is_email($recipient)) {
        rapkalin_mail_redirect_with_notice(false, __('The recipient address is not valid.', 'rapkalin-mail'));
    }

    $failure = '';
    $captureFailure = static function (WP_Error $error) use (&$failure): void {
        $failure = $error->get_error_message();
    };
    add_action('wp_mail_failed', $captureFailure);

    $isSent = wp_mail(
        $recipient,
        /* translators: %s: site name */
        sprintf(__('[%s] Test email', 'rapkalin-mail'), get_bloginfo('name')),
        sprintf(
            /* translators: 1: date and time, 2: transport description */
            __("This is a test email sent on %1\$s.\nTransport: %2\$s", 'rapkalin-mail'),
            current_time('mysql'),
            rapkalin_mail_describe_transport(rapkalin_mail_config()),
        ),
    );

    remove_action('wp_mail_failed', $captureFailure);

    if ($isSent) {
        /* translators: %s: recipient address */
        rapkalin_mail_redirect_with_notice(true, sprintf(__('Test email sent to %s.', 'rapkalin-mail'), $recipient));
    }

    rapkalin_mail_redirect_with_notice(
        false,
        /* translators: %s: error returned by the mailer */
        sprintf(__('The test email could not be sent: %s', 'rapkalin-mail'), $failure ?: __('unknown error.', 'rapkalin-mail')),
    );
}

function rapkalin_mail_describe_transport(array $config): string
{
    if ($config['transport'] === RapkalinMailSettings::TRANSPORT_SENDMAIL) {
        return __('server sendmail binary', 'rapkalin-mail');
    }

    return sprintf('SMTP %s:%d', $config['smtp_host'], $config['smtp_port']);
}

function rapkalin_mail_render_notice(): void
{
    $notice = get_transient(rapkalin_mail_notice_key());

    if (! is_array($notice)) {
        return;
    }

    delete_transient(rapkalin_mail_notice_key());
    printf(
        '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
        $notice['is_success'] ? 'success' : 'error',
        esc_html((string) $notice['message']),
    );
}

/**
 * Hides the SMTP fields while Sendmail is selected, without JavaScript: a
 * browser without :has() support simply shows every field.
 */
function rapkalin_mail_print_styles(): void
{
    echo '<style>.rapkalin-mail-settings:has(input[name="transport"][value="sendmail"]:checked) .rapkalin-mail-smtp { display: none; }</style>';
}

function rapkalin_mail_render_page(): void
{
    if (! current_user_can(RAPKALIN_MAIL_CAPABILITY)) {
        wp_die(esc_html__('You are not allowed to manage email settings.', 'rapkalin-mail'), 403);
    }

    $settings = array_merge(RapkalinMailSettings::defaults(), rapkalin_mail_stored_settings());
    $config = rapkalin_mail_config();
    $transportLabel = '<code>' . esc_html(rapkalin_mail_describe_transport($config)) . '</code>';
    $passwordHint = $settings['smtp_password'] !== ''
        ? __('A password is stored, encrypted. Leave empty to keep it.', 'rapkalin-mail')
        : __('No password stored.', 'rapkalin-mail');
    /* translators: %s: transport description */
    $overrideNotice = __('SMTP_HOST is set in the .env file: it overrides the settings below, and emails currently go through %s.', 'rapkalin-mail');
    /* translators: %s: transport description */
    $testNotice = __('Uses the saved settings, currently: %s.', 'rapkalin-mail');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Email', 'rapkalin-mail'); ?></h1>

        <?php rapkalin_mail_render_notice(); ?>

        <?php if ($config['source'] === 'env') : ?>
            <div class="notice notice-warning"><p><?php printf(esc_html($overrideNotice), $transportLabel); ?></p></div>
        <?php elseif ($config['is_password_unreadable']) : ?>
            <div class="notice notice-error"><p>
                <?php esc_html_e('The stored SMTP password can no longer be decrypted, most likely because the WordPress salts changed. Enter it again.', 'rapkalin-mail'); ?>
            </p></div>
        <?php endif; ?>

        <form class="rapkalin-mail-settings" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="<?php echo esc_attr(RAPKALIN_MAIL_SAVE_ACTION); ?>">
            <?php wp_nonce_field(RAPKALIN_MAIL_SAVE_ACTION); ?>

            <h2><?php esc_html_e('Transport', 'rapkalin-mail'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Send emails with', 'rapkalin-mail'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="transport" value="<?php echo esc_attr(RapkalinMailSettings::TRANSPORT_SENDMAIL); ?>"
                                    <?php checked($settings['transport'], RapkalinMailSettings::TRANSPORT_SENDMAIL); ?>>
                                <?php esc_html_e('Sendmail — the mail binary of the server', 'rapkalin-mail'); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="transport" value="<?php echo esc_attr(RapkalinMailSettings::TRANSPORT_SMTP); ?>"
                                    <?php checked($settings['transport'], RapkalinMailSettings::TRANSPORT_SMTP); ?>>
                                <?php esc_html_e('SMTP — the relay configured below', 'rapkalin-mail'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>

            <div class="rapkalin-mail-smtp">
            <h2><?php esc_html_e('SMTP relay', 'rapkalin-mail'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="rapkalin-mail-host"><?php esc_html_e('Host', 'rapkalin-mail'); ?></label></th>
                    <td><input id="rapkalin-mail-host" name="smtp_host" type="text" class="regular-text"
                            value="<?php echo esc_attr($settings['smtp_host']); ?>" placeholder="smtp.example.com"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="rapkalin-mail-port"><?php esc_html_e('Port', 'rapkalin-mail'); ?></label></th>
                    <td><input id="rapkalin-mail-port" name="smtp_port" type="number" min="1" max="65535" class="small-text"
                            value="<?php echo esc_attr((string) $settings['smtp_port']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="rapkalin-mail-encryption"><?php esc_html_e('Encryption', 'rapkalin-mail'); ?></label></th>
                    <td>
                        <select id="rapkalin-mail-encryption" name="smtp_encryption">
                            <option value="" <?php selected($settings['smtp_encryption'], ''); ?>><?php esc_html_e('None', 'rapkalin-mail'); ?></option>
                            <option value="tls" <?php selected($settings['smtp_encryption'], 'tls'); ?>><?php esc_html_e('STARTTLS (usually port 587)', 'rapkalin-mail'); ?></option>
                            <option value="ssl" <?php selected($settings['smtp_encryption'], 'ssl'); ?>><?php esc_html_e('SSL/TLS (usually port 465)', 'rapkalin-mail'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rapkalin-mail-username"><?php esc_html_e('Username', 'rapkalin-mail'); ?></label></th>
                    <td>
                        <input id="rapkalin-mail-username" name="smtp_username" type="text" class="regular-text" autocomplete="off"
                            value="<?php echo esc_attr($settings['smtp_username']); ?>">
                        <p class="description"><?php esc_html_e('Leave empty for a relay without authentication.', 'rapkalin-mail'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rapkalin-mail-password"><?php esc_html_e('Password', 'rapkalin-mail'); ?></label></th>
                    <td>
                        <input id="rapkalin-mail-password" name="smtp_password" type="password" class="regular-text" autocomplete="new-password" value="">
                        <p class="description"><?php echo esc_html($passwordHint); ?></p>
                    </td>
                </tr>
            </table>
            </div>

            <h2><?php esc_html_e('Sender', 'rapkalin-mail'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="rapkalin-mail-from-email"><?php esc_html_e('From address', 'rapkalin-mail'); ?></label></th>
                    <td>
                        <input id="rapkalin-mail-from-email" name="from_email" type="email" class="regular-text"
                            value="<?php echo esc_attr($settings['from_email']); ?>">
                        <p class="description"><?php esc_html_e('Leave empty to use MAIL_FROM from the .env file.', 'rapkalin-mail'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rapkalin-mail-from-name"><?php esc_html_e('From name', 'rapkalin-mail'); ?></label></th>
                    <td>
                        <input id="rapkalin-mail-from-name" name="from_name" type="text" class="regular-text"
                            value="<?php echo esc_attr($settings['from_name']); ?>">
                        <p class="description"><?php esc_html_e('Leave empty to use MAIL_FROM_NAME from the .env file.', 'rapkalin-mail'); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Save settings', 'rapkalin-mail')); ?>
        </form>

        <hr>

        <h2><?php esc_html_e('Send a test email', 'rapkalin-mail'); ?></h2>
        <p class="description"><?php printf(esc_html($testNotice), $transportLabel); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="<?php echo esc_attr(RAPKALIN_MAIL_TEST_ACTION); ?>">
            <?php wp_nonce_field(RAPKALIN_MAIL_TEST_ACTION); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="rapkalin-mail-recipient"><?php esc_html_e('Recipient', 'rapkalin-mail'); ?></label></th>
                    <td><input id="rapkalin-mail-recipient" name="recipient" type="email" class="regular-text" required
                            value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>"></td>
                </tr>
            </table>
            <?php submit_button(__('Send test email', 'rapkalin-mail'), 'secondary'); ?>
        </form>
    </div>
    <?php
}
