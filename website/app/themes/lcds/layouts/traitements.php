<?php

/**
 * Section « les différents traitements » : accordéon.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$cta = lcds_sub_field('cta');
$cta = is_array($cta) ? $cta : [];
$rows = lcds_sub_field('entrees');
$items = [];

foreach ((is_array($rows) ? $rows : []) as $row) {
    $items[] = [
        'title' => trim((string) ($row['titre'] ?? '')),
        'text' => (string) ($row['texte'] ?? ''),
        'open' => ! empty($row['ouvert']),
    ];
}

get_template_part('components/block-treatments', null, [
    'label' => lcds_sub_field_text('etiquette'),
    'dot' => lcds_sub_field_text('puce') ?: 'turquoise',
    'items' => $items,
    'cta' => [
        'label' => trim((string) ($cta['title'] ?? '')),
        'url' => trim((string) ($cta['url'] ?? '')),
    ],
]);
