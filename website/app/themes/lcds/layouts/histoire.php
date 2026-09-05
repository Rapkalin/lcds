<?php

/**
 * Section « l'histoire » : texte et galerie défilable.
 *
 * Les largeurs du rail viennent de LcdsMediaShape : le contributeur choisit une
 * forme nommée, jamais un nombre de pixels.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$cta = lcds_sub_field('cta');
$cta = is_array($cta) ? $cta : [];
$rows = lcds_sub_field('galerie');
$items = [];

foreach ((is_array($rows) ? $rows : []) as $row) {
    $shape = LcdsMediaShape::fromValue($row['forme'] ?? null, LcdsMediaShape::Medium);
    $images = [lcds_attachment_id($row['image'] ?? null)];

    if ($shape->isPair()) {
        $images[] = lcds_attachment_id($row['image_2'] ?? null);
    }

    $items[] = ['width' => $shape->width(), 'images' => $images];
}

get_template_part('components/block-intro', null, [
    'label' => lcds_sub_field_text('etiquette'),
    'dot' => lcds_sub_field_text('puce') ?: 'turquoise',
    'text' => (string) lcds_sub_field('texte'),
    'cta' => [
        'label' => trim((string) ($cta['title'] ?? '')),
        'url' => trim((string) ($cta['url'] ?? '')),
    ],
    'gallery' => $items === [] ? [] : [
        'label' => lcds_sub_field_text('galerie_libelle') ?: __('Galerie', 'lcds'),
        'items' => $items,
    ],
]);
