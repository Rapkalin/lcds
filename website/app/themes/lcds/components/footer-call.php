<?php

/**
 * Bloc d'appel du pied de page : un titre et ses boutons contournés.
 *
 * Arguments (via get_template_part) : le tableau d'une rangée du répéteur
 * « Blocs d'appel » — `titre` et `liens`.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$title = isset($args['titre']) ? trim((string) $args['titre']) : '';

if ($title === '') {
    return;
}

$links = isset($args['liens']) && is_array($args['liens']) ? $args['liens'] : [];
?>

<div class="footer-call">
    <h2 class="footer-call__title"><?php echo esc_html($title); ?></h2>

    <?php if ($links !== []) : ?>
        <div class="footer-call__actions">
            <?php foreach ($links as $row) : ?>
                <?php $link = is_array($row['lien'] ?? null) ? $row['lien'] : []; ?>
                <?php get_template_part('components/cta', null, [
                    'label' => trim((string) ($link['title'] ?? '')),
                    'url' => trim((string) ($link['url'] ?? '')),
                    'variant' => 'outline',
                ]); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
