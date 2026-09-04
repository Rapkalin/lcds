<?php

/**
 * Bloc « LCDS — Parcours en étapes » : rendu serveur.
 *
 * Ne fait que lire les champs et déléguer au composant `block-journey`.
 *
 * La numérotation et le remplissage de la barre se déduisent du NOMBRE d'étapes
 * saisies : en ajouter une renumérote et rééchelonne la progression sans autre
 * intervention.
 *
 * Variables fournies par ACF : $block, $is_preview, $post_id.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$rows = lcds_field('etapes');
$steps = [];

foreach ((is_array($rows) ? $rows : []) as $row) {
    $medias = [];

    foreach ((is_array($row['visuels'] ?? null) ? $row['visuels'] : []) as $frame) {
        $shape = LcdsMediaShape::fromValue($frame['forme'] ?? null, LcdsMediaShape::StepWide);
        $medias[] = [
            'width' => $shape->width(),
            'image' => lcds_attachment_id($frame['image'] ?? null),
        ];
    }

    $steps[] = [
        'title' => trim((string) ($row['titre'] ?? '')),
        'text' => (string) ($row['texte'] ?? ''),
        'duration' => trim((string) ($row['duree'] ?? '')),
        'images' => $medias,
    ];
}

$journey = [
    'label' => lcds_field_text('etiquette'),
    'dot' => lcds_field_text('puce') ?: 'orange',
    'steps' => $steps,
];

if ($steps === []) {
    if (! empty($is_preview)) {
        printf(
            '<p class="lcds-block-hint">%s</p>',
            esc_html__('Parcours : ajoutez au moins une étape dans le panneau de droite.', 'lcds'),
        );
    }

    return;
}

get_template_part('components/block-journey', null, $journey);
