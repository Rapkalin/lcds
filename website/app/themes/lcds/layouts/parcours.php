<?php

/**
 * Section « le parcours de soin » : étapes parcourues au défilement.
 *
 * Les largeurs des visuels d'étape viennent de LcdsMediaShape.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$rows = lcds_sub_field('etapes');
$steps = [];

foreach ((is_array($rows) ? $rows : []) as $row) {
    $images = [];

    foreach ((is_array($row['visuels'] ?? null) ? $row['visuels'] : []) as $frame) {
        $shape = LcdsMediaShape::fromValue($frame['forme'] ?? null, LcdsMediaShape::StepWide);
        $images[] = [
            'width' => $shape->width(),
            'image' => lcds_attachment_id($frame['image'] ?? null),
        ];
    }

    $steps[] = [
        'title' => trim((string) ($row['titre'] ?? '')),
        'text' => (string) ($row['texte'] ?? ''),
        'duration' => trim((string) ($row['duree'] ?? '')),
        'images' => $images,
    ];
}

get_template_part('components/block-journey', null, [
    'label' => lcds_sub_field_text('etiquette'),
    'dot' => lcds_sub_field_text('puce') ?: 'orange',
    'steps' => $steps,
]);
