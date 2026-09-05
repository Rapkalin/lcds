<?php

/**
 * Section « informations pratiques ».
 *
 * Les icônes viennent de LcdsInfoIcon : le contributeur choisit un nom, jamais
 * un chemin de fichier.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$rows = lcds_sub_field('entrees');
$entries = [];

foreach ((is_array($rows) ? $rows : []) as $row) {
    $link = is_array($row['lien'] ?? null) ? $row['lien'] : [];

    $entries[] = [
        'icon' => (string) ($row['icone'] ?? ''),
        'title' => trim((string) ($row['titre'] ?? '')),
        'overline' => trim((string) ($row['surtitre'] ?? '')),
        'text' => (string) ($row['texte'] ?? ''),
        'cta' => [
            'label' => trim((string) ($link['title'] ?? '')),
            'url' => trim((string) ($link['url'] ?? '')),
        ],
    ];
}

get_template_part('components/block-info', null, [
    'label' => lcds_sub_field_text('etiquette'),
    'dot' => lcds_sub_field_text('puce') ?: 'orange',
    'image' => lcds_attachment_id(lcds_sub_field('visuel')),
    'entries' => $entries,
]);
