<?php

/**
 * Section « l'app mobile ».
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$link = lcds_sub_field('lien');
$link = is_array($link) ? $link : [];

get_template_part('components/block-app', null, [
    'label' => lcds_sub_field_text('etiquette'),
    'dot' => lcds_sub_field_text('puce') ?: 'orange',
    'title' => lcds_sub_field_text('titre'),
    'image' => lcds_attachment_id(lcds_sub_field('visuel')),
    'code' => lcds_attachment_id(lcds_sub_field('code')),
    'url' => trim((string) ($link['url'] ?? '')),
]);
