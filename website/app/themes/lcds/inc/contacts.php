<?php

use PHPMailer\PHPMailer\PHPMailer;

add_action('wp_ajax_submit_dynamic_form', 'submit_dynamic_form');
add_action('wp_ajax_nopriv_submit_dynamic_form', 'submit_dynamic_form');

function configure_smtp(PHPMailer $phpmailer, string $from, string $message): void
{
    $env = PROJECT_ENV_CONFIG;
    $phpmailer->isSMTP();
    $phpmailer->isHTML();
    $phpmailer->Host = $env['HOST']; // SMTP SERVER
    $phpmailer->SMTPAuth = (bool) $env['SMTPAUTH'];
    $phpmailer->Port = (int) $env['PORT'];
    $phpmailer->Username = $env['USERNAME_MAIL'];
    $phpmailer->Password = $env['PASSWORD_MAIL'];
    $phpmailer->SMTPSecure = $env['SMTPSECURE'];
    $phpmailer->CharSet = 'UTF-8';
    $senderMail = $env['FROM_MAIL'];
    $fromName = $env['FROMNAME_MAIL'];
    $phpmailer->setFrom($senderMail, $fromName);
    $to = $env['TO_MAIL'];
    $phpmailer->addAddress($to);
    $phpmailer->Subject = 'Nouveau message depuis le formulaire de contact';
    $phpmailer->Body = $message;
    $phpmailer->AltBody = strip_tags($message);
}

function submit_dynamic_form(): void {
    $errors = [];

    foreach ($_POST as $key => $value) {
        if (empty($value)) {
            $errors[$key] = 'Ce champ est requis';
        }
    }

    if (!empty($errors)) {
        wp_send_json_error($errors);
    }

    $from = $_POST['email_from'];
    $message = '';

    foreach ($_POST as $key => $value) {
        if (in_array($key, ['action', 'email_from'])) continue;

        $message .= "<p>" . ucfirst($key) . " : " . sanitize_text_field($value) . "</p>";
    }

    try {
        $attachments = [];

        if (!empty($_FILES)) {
            $message .= "<p>Fichiers :</p>";

            foreach ($_FILES as $inputName => $fileData) {

                // multiple uploaded files
                foreach ($fileData['name'] as $index => $name) {

                    if (empty($name)) continue;

                    $tmpName = $fileData['tmp_name'][$index];
                    $size = $fileData['size'][$index];
                    $error = $fileData['error'][$index];

                    if ($error !== UPLOAD_ERR_OK) continue;

                    $fileType = wp_check_filetype_and_ext($tmpName, $name);
                    $allowed = [
                        'application/pdf',

                        // Word
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

                        // PowerPoint
                        'application/vnd.ms-powerpoint', // .ppt
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation' // .pptx
                    ];


                    if (!in_array($fileType['type'], $allowed)) {
                        wp_send_json_error("Fichier invalide : $name");
                    }

                    if ($size > 5 * 1024 * 1024) {
                        wp_send_json_error("Fichier trop volumineux : $name");
                    }

                    $upload_dir = wp_upload_dir();
                    $filePath = $upload_dir['path'] . '/' . uniqid() . '-' . basename($name);

                    move_uploaded_file($tmpName, $filePath);

                    $attachments[] = $filePath;

                    $message .= "<p>$inputName : $name</p>";
                }
            }
        }

        $mail = new PHPMailer(true);
        configure_smtp($mail, $from, $message);

        foreach ($attachments as $file) {
            $mail->addAttachment($file);
        }

        $mail->send();

        foreach ($attachments as $file) {
            if (file_exists($file)) unlink($file);
        }

        wp_send_json_success();

    } catch (\Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

function recaptchaCheck() {
    $recaptcha_secret = 'votre_cle_secrete_recaptcha';
    $recaptcha_response = $_POST['g-recaptcha-response'];
    $recaptcha_url = "https://www.google.com/recaptcha/api/siteverify?secret=$recaptcha_secret&response=$recaptcha_response";
    $recaptcha_data = json_decode(file_get_contents($recaptcha_url));

    if (!$recaptcha_data->success) {
        wp_send_json_error('reCAPTCHA verification failed');
    }
}

function getFormGroup(string $type, array $data = []): void {
    switch ($type) {
        case 'text':
            ?>
            <div class="form-group group-text group-label">
                <div class="error-container"></div>
                <label for="<?= $data['label'] ?>"><?= $data['label'] ?></label>
                <input type="text" id="<?= $data['label'] ?>" name="<?= $data['label'] ?>" placeholder="<?= $data['label'] ?>">
            </div>
            <?php
            break;
        case 'email':
            ?>
                <div class="form-group group-text group-label">
                    <div class="error-container"></div>
                    <label for="email"><?= $data['label'] ?></label>
                    <input autocomplete type="email" id="email" name="email" placeholder="<?= $data['label'] ?>">
                </div>
            <?php
            break;
        case 'cities':
            ?>
                <div class="form-group group-text group-cities">
                    <div class="error-container"></div>
                    <div class="cities-container">
                        <div class="city-title">Ville</div>
                        <div class="cities-option">
                            <?php foreach ($data['cities'] as $city): ?>
                                <div class="city-option">
                                    <input type="radio" id="<?= $city ?>" name="city" value="<?= $city ?>">
                                    <label for="<?= $city ?>"><?= $city ?></label>
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
                    <label for="message"><?= $data['label'] ?></label>
                    <textarea id="message" name="message" placeholder="<?= $data['label'] ?>"></textarea>
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
                    name="<?= htmlspecialchars($data['label']) ?>[]"
                    id="<?= htmlspecialchars($data['label']) ?>"
                >

                <label for="<?= htmlspecialchars($data['label']) ?>" class="custom-file-label">
                    <?= htmlspecialchars($data['label']) ?>
                    <?php get_template_part("components/svg-arrow-down"); ?>
                </label>

                <div class="file-info-container" style="display: none;">
                    <div class="file-name"></div>
                    <div class="file-size"></div>
                </div>
            </div>
            <?php break;
        default:
            break;
    }
}
