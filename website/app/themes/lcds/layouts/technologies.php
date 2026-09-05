<?php

/**
 * Section « les technologies » : rail de cartes inclinées.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$cta = lcds_sub_field('cta');
$cta = is_array($cta) ? $cta : [];
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
    'cta' => [
        'label' => trim((string) ($cta['title'] ?? '')),
        'url' => trim((string) ($cta['url'] ?? '')),
    ],
    'cards' => $cards,
]);
