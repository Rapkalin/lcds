<?php

/**
 * Bloc « LCDS — Accordéon » : rendu serveur.
 *
 * Ne fait que lire les champs et déléguer au composant `block-treatments`.
 *
 * Variables fournies par ACF : $block, $is_preview, $post_id.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$cta = lcds_field('cta');
$cta = is_array($cta) ? $cta : [];
$rows = lcds_field('entrees');
$items = [];

foreach ((is_array($rows) ? $rows : []) as $row) {
    $items[] = [
        'title' => trim((string) ($row['titre'] ?? '')),
        'text' => (string) ($row['texte'] ?? ''),
        'open' => ! empty($row['ouvert']),
    ];
}

$treatments = [
    'label' => lcds_field_text('etiquette'),
    'dot' => lcds_field_text('puce') ?: 'turquoise',
    'items' => $items,
    'cta' => [
        'label' => trim((string) ($cta['title'] ?? '')),
        'url' => trim((string) ($cta['url'] ?? '')),
    ],
];

if ($items === []) {
    if (! empty($is_preview)) {
        printf(
            '<p class="lcds-block-hint">%s</p>',
            esc_html__('Accordéon : ajoutez au moins une entrée dans le panneau de droite.', 'lcds'),
        );
    }

    return;
}

get_template_part('components/block-treatments', null, $treatments);
