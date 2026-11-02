<?php

/**
 * Section « le parcours de soin » : une étape par vue, parcourues au slider.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$rows = lcds_sub_field('etapes');
$steps = [];

foreach ((is_array($rows) ? $rows : []) as $row) {
    $steps[] = [
        'title' => trim((string) ($row['titre'] ?? '')),
        'text' => (string) ($row['texte'] ?? ''),
        'duration' => trim((string) ($row['duree'] ?? '')),
        'image' => lcds_attachment_id($row['visuel'] ?? null),
    ];
}

get_template_part('components/block-journey', null, [
    'label' => lcds_sub_field_text('etiquette'),
    'dot' => lcds_sub_field_text('puce') ?: 'orange',
    'steps' => $steps,
]);
