<?php

/**
 * Bloc « LCDS — Carrousel de cartes » : rendu serveur.
 *
 * Ne fait que lire les champs et déléguer au composant `block-techno`, qui
 * porte le balisage, l'ondulation des cartes et la réutilisation du rail de la
 * galerie d'intro.
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
$rows = lcds_field('cartes');
$cards = [];

foreach ((is_array($rows) ? $rows : []) as $row) {
    $cards[] = [
        'title' => trim((string) ($row['titre'] ?? '')),
        'text' => (string) ($row['texte'] ?? ''),
        'image' => lcds_attachment_id($row['image'] ?? null),
        'open' => ! empty($row['ouvert']),
    ];
}

$techno = [
    'label' => lcds_field_text('etiquette'),
    'dot' => lcds_field_text('puce') ?: 'orange',
    'cta' => [
        'label' => trim((string) ($cta['title'] ?? '')),
        'url' => trim((string) ($cta['url'] ?? '')),
    ],
    'cards' => $cards,
];

if ($techno['label'] === '' && $cards === []) {
    if (! empty($is_preview)) {
        printf(
            '<p class="lcds-block-hint">%s</p>',
            esc_html__('Carrousel de cartes : renseignez l’étiquette et les cartes dans le panneau de droite.', 'lcds'),
        );
    }

    return;
}

get_template_part('components/block-techno', null, $techno);
