<?php

/**
 * Contact form: SMTP transport, AJAX handler and field rendering.
 *
 * Mail goes through wp_mail() and the `phpmailer_init` hook rather than a
 * separately-required PHPMailer: WordPress ships its own copy of the
 * PHPMailer\PHPMailer\PHPMailer class and loads it with a bare require_once, so
 * instantiating a Composer copy of the same class in the same request is a fatal
 * redeclaration waiting to happen.
 *
 * @package lcds
 */

use function Env\env;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Nonce action guarding the public form submission.
 */
const LCDS_CONTACT_NONCE_ACTION = 'lcds_contact_form';

/**
 * Form fields that carry plumbing, not user answers, and are never echoed into
 * the message body.
 */
const LCDS_CONTACT_RESERVED_FIELDS = ['action', 'lcds_nonce', 'page_id', 'email_from'];

/**
 * Attachment limits. Kept low on purpose: this is an unauthenticated endpoint.
 */
const LCDS_CONTACT_MAX_FILE_SIZE = 5 * MB_IN_BYTES;
const LCDS_CONTACT_MAX_FILES = 5;

/**
 * Upload allow-list, as extension => MIME type.
 *
 * Both halves are checked: the extension decides which MIME is acceptable, and
 * wp_check_filetype_and_ext() re-derives the real type from the file contents,
 * so a .pdf that is actually a PHP script is rejected.
 *
 * @return array<string, string>
 */
function lcds_contact_allowed_uploads(): array
{
    return [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];
}

/**
 * Sender address, from the environment.
 *
 * This MUST go through the `wp_mail_from` filter and not through
 * `phpmailer_init`: wp_mail() calls setFrom() BEFORE firing phpmailer_init, so a
 * From set in that hook arrives too late. WordPress' own default is
 * `wordpress@{host}`, which PHPMailer rejects outright when the host carries no
 * dot (`localhost`, `localhost:8080`) — every send would fail.
 */
function lcds_mail_from(string $from): string
{
    $configured = (string) env('MAIL_FROM');

    return is_email($configured) ? $configured : $from;
}
add_filter('wp_mail_from', 'lcds_mail_from');

/**
 * Sender name, from the environment.
 */
function lcds_mail_from_name(string $name): string
{
    $configured = (string) env('MAIL_FROM_NAME');

    return $configured !== '' ? $configured : $name;
}
add_filter('wp_mail_from_name', 'lcds_mail_from_name');

/**
 * Point PHPMailer at the project SMTP relay when one is configured.
 *
 * Without SMTP_HOST we leave WordPress on PHP's mail(), which keeps local
 * environments working without credentials.
 */
function lcds_configure_smtp(\PHPMailer\PHPMailer\PHPMailer $phpmailer): void
{
    $host = (string) env('SMTP_HOST');

    if ($host === '') {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host = $host;
    $phpmailer->Port = (int) (env('SMTP_PORT') ?: 587);
    $phpmailer->SMTPAuth = (bool) env('SMTP_AUTH');
    $phpmailer->Username = (string) env('SMTP_USERNAME');
    $phpmailer->Password = (string) env('SMTP_PASSWORD');

    $secure = (string) env('SMTP_SECURE');

    if ($secure === '') {
        // Relais local sans chiffrement (Mailpit) : PHPMailer tenterait
        // STARTTLS d'office et l'envoi échouerait.
        $phpmailer->SMTPAutoTLS = false;
    } else {
        $phpmailer->SMTPSecure = $secure;
    }

    // The From is already set by the wp_mail_from / wp_mail_from_name filters
    // above, which run early enough for wp_mail() to accept it.
    $phpmailer->CharSet = 'UTF-8';
}
add_action('phpmailer_init', 'lcds_configure_smtp');

/**
 * Hidden inputs every contact form must carry: the nonce that authenticates the
 * submission, and the page whose ACF settings decide the recipient.
 */
function lcds_contact_form_hidden_fields(int $page_id): void
{
    wp_nonce_field(LCDS_CONTACT_NONCE_ACTION, 'lcds_nonce');

    printf('<input type="hidden" name="page_id" value="%d">', $page_id);
}

/**
 * Resolves the recipient SERVER-SIDE from the submitted page.
 *
 * The address is never read from the request: taking it from POST would turn
 * this endpoint into an open relay that mails anywhere on demand.
 */
function lcds_contact_recipient(int $page_id): string
{
    $configured = $page_id > 0 ? (string) get_field('mail_choice', $page_id) : '';

    if (is_email($configured)) {
        return $configured;
    }

    $fallback = (string) env('MAIL_TO');

    return is_email($fallback) ? $fallback : '';
}

/**
 * Collects the user's answers as a sanitized label => value map.
 *
 * @return array<string, string>
 */
function lcds_contact_collect_answers(array $source): array
{
    $answers = [];

    foreach ($source as $key => $value) {
        if (in_array($key, LCDS_CONTACT_RESERVED_FIELDS, true) || is_array($value)) {
            continue;
        }

        $label = sanitize_text_field(wp_unslash((string) $key));
        $answers[$label] = sanitize_textarea_field(wp_unslash((string) $value));
    }

    return $answers;
}

/**
 * Validates the uploaded files and returns them as displayName => tmpPath.
 *
 * Files are attached straight from PHP's temporary directory: writing them into
 * web-reachable uploads/ first — even briefly — would publish every CV the form
 * receives. PHP removes the temp files when the request ends.
 *
 * @return array<string, string>
 * @throws \RuntimeException When a file is rejected.
 */
function lcds_contact_collect_attachments(array $files): array
{
    $allowed = lcds_contact_allowed_uploads();
    $attachments = [];

    foreach ($files as $field) {
        if (! is_array($field) || ! isset($field['name'])) {
            continue;
        }

        // Normalize both the single-file and the multiple-files ($name[]) shapes.
        $names = (array) $field['name'];
        $tmp_names = (array) $field['tmp_name'];
        $sizes = (array) $field['size'];
        $errors = (array) $field['error'];

        foreach ($names as $index => $name) {
            $name = (string) $name;

            if ($name === '' || ($errors[$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if (($errors[$index] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                throw new \RuntimeException(sprintf(__('Échec du téléversement : %s', 'lcds'), sanitize_file_name($name)));
            }

            $tmp_name = (string) ($tmp_names[$index] ?? '');

            if (! is_uploaded_file($tmp_name)) {
                throw new \RuntimeException(__('Fichier invalide.', 'lcds'));
            }

            if ((int) ($sizes[$index] ?? 0) > LCDS_CONTACT_MAX_FILE_SIZE) {
                throw new \RuntimeException(sprintf(__('Fichier trop volumineux : %s', 'lcds'), sanitize_file_name($name)));
            }

            $safe_name = sanitize_file_name($name);
            $checked = wp_check_filetype_and_ext($tmp_name, $safe_name, $allowed);

            if ($checked['ext'] === false || $checked['type'] === false) {
                throw new \RuntimeException(sprintf(__('Type de fichier non autorisé : %s', 'lcds'), $safe_name));
            }

            if (count($attachments) >= LCDS_CONTACT_MAX_FILES) {
                throw new \RuntimeException(__('Trop de fichiers joints.', 'lcds'));
            }

            $attachments[$safe_name] = $tmp_name;
        }
    }

    return $attachments;
}

/**
 * Builds the HTML mail body. Every value is escaped: the body is attacker-controlled.
 *
 * @param array<string, string> $answers
 * @param array<string, string> $attachments
 */
function lcds_contact_build_message(array $answers, array $attachments): string
{
    $message = '';

    foreach ($answers as $label => $value) {
        $message .= sprintf('<p><strong>%s</strong> : %s</p>', esc_html(ucfirst($label)), esc_html($value));
    }

    if ($attachments !== []) {
        $message .= '<p><strong>' . esc_html__('Fichiers joints', 'lcds') . '</strong></p><ul>';

        foreach (array_keys($attachments) as $name) {
            $message .= '<li>' . esc_html($name) . '</li>';
        }

        $message .= '</ul>';
    }

    return $message;
}

/**
 * AJAX handler for the public contact form.
 */
function lcds_submit_contact_form(): void
{
    if (! check_ajax_referer(LCDS_CONTACT_NONCE_ACTION, 'lcds_nonce', false)) {
        wp_send_json_error(__('Session expirée, merci de recharger la page.', 'lcds'), 403);
    }

    $page_id = isset($_POST['page_id']) ? absint(wp_unslash($_POST['page_id'])) : 0;
    $recipient = lcds_contact_recipient($page_id);

    if ($recipient === '') {
        wp_send_json_error(__('Formulaire mal configuré.', 'lcds'), 500);
    }

    $reply_to = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

    if (! is_email($reply_to)) {
        wp_send_json_error(['email' => __('Adresse e-mail invalide', 'lcds')]);
    }

    $answers = lcds_contact_collect_answers($_POST);
    $missing = array_keys(array_filter($answers, static fn(string $value): bool => $value === ''));

    if ($missing !== []) {
        wp_send_json_error(array_fill_keys($missing, __('Ce champ est requis', 'lcds')));
    }

    try {
        $attachments = lcds_contact_collect_attachments($_FILES);
    } catch (\RuntimeException $exception) {
        wp_send_json_error($exception->getMessage());
    }

    $sent = wp_mail(
        $recipient,
        __('Nouveau message depuis le formulaire de contact', 'lcds'),
        lcds_contact_build_message($answers, $attachments),
        [
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $reply_to,
        ],
        $attachments,
    );

    if (! $sent) {
        wp_send_json_error(__("L'envoi a échoué, merci de réessayer.", 'lcds'), 500);
    }

    wp_send_json_success();
}
add_action('wp_ajax_submit_dynamic_form', 'lcds_submit_contact_form');
add_action('wp_ajax_nopriv_submit_dynamic_form', 'lcds_submit_contact_form');

/**
 * Renders one field of the dynamic contact form.
 *
 * @param array<string, mixed> $data
 */
function getFormGroup(string $type, array $data = []): void
{
    $label = (string) ($data['label'] ?? '');

    switch ($type) {
        case 'text':
            ?>
            <div class="form-group group-text group-label">
                <div class="error-container"></div>
                <label for="<?= esc_attr($label) ?>"><?= esc_html($label) ?></label>
                <input type="text" id="<?= esc_attr($label) ?>" name="<?= esc_attr($label) ?>" placeholder="<?= esc_attr($label) ?>">
            </div>
            <?php
            break;
        case 'email':
            ?>
            <div class="form-group group-text group-label">
                <div class="error-container"></div>
                <label for="email"><?= esc_html($label) ?></label>
                <input autocomplete="email" type="email" id="email" name="email" placeholder="<?= esc_attr($label) ?>">
            </div>
            <?php
            break;
        case 'cities':
            ?>
            <div class="form-group group-text group-cities">
                <div class="error-container"></div>
                <div class="cities-container">
                    <div class="city-title"><?= esc_html__('Ville', 'lcds') ?></div>
                    <div class="cities-option">
                        <?php foreach ((array) ($data['cities'] ?? []) as $city): ?>
                            <div class="city-option">
                                <input type="radio" id="<?= esc_attr((string) $city) ?>" name="city" value="<?= esc_attr((string) $city) ?>">
                                <label for="<?= esc_attr((string) $city) ?>"><?= esc_html((string) $city) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php
            break;
        case 'text_area':
            ?>
            <div class="form-group group-text group-label">
                <label for="message"><?= esc_html($label) ?></label>
                <textarea id="message" name="message" placeholder="<?= esc_attr($label) ?>"></textarea>
            </div>
            <?php
            break;
        case 'file':
            ?>
            <div class="form-group group-file">
                <input
                    type="file"
                    multiple
                    class="custom-file-input"
                    name="<?= esc_attr($label) ?>[]"
                    id="<?= esc_attr($label) ?>"
                    accept="<?= esc_attr('.' . implode(',.', array_keys(lcds_contact_allowed_uploads()))) ?>"
                >

                <label for="<?= esc_attr($label) ?>" class="custom-file-label">
                    <?= esc_html($label) ?>
                </label>

                <div class="file-info-container" style="display: none;">
                    <div class="file-name"></div>
                    <div class="file-size"></div>
                </div>
            </div>
            <?php
            break;
        default:
            break;
    }
}
