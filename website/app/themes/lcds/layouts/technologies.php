<?php

/**
 * Section « les technologies » : rail de cartes inclinées.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$rows = lcds_sub_field('cartes');
$cards = [];

foreach ((is_array($rows) ? $rows : []) as $row) {
    $cards[] = [
        'title' => trim((string) ($row['titre'] ?? '')),
        'text' => (string) ($row['texte'] ?? ''),
        'image' => lcds_attachment_id($row['image'] ?? null),
        'open' => ! empty($row['ouvert']),
    ];
}

get_template_part('components/block-techno', null, [
    'label' => lcds_sub_field_text('etiquette'),
    'dot' => lcds_sub_field_text('puce') ?: 'orange',
    'cta' => lcds_link(lcds_sub_field('cta')),
    'cards' => $cards,
]);
