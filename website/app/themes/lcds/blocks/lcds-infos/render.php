<?php

/**
 * Bloc « LCDS — Informations pratiques » : rendu serveur.
 *
 * Ne fait que lire les champs et déléguer au composant `block-info`, qui porte
 * le balisage et la mise en forme.
 *
 * Les icônes viennent de LcdsInfoIcon : le contributeur choisit un nom, jamais
 * un chemin de fichier.
 *
 * Variables fournies par ACF : $block, $is_preview, $post_id.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$rows = lcds_field('entrees');
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

$infos = [
    'label' => lcds_field_text('etiquette'),
    'dot' => lcds_field_text('puce') ?: 'orange',
    'image' => lcds_attachment_id(lcds_field('visuel')),
    'entries' => $entries,
];

if ($infos['label'] === '' && $entries === []) {
    if (! empty($is_preview)) {
        printf(
            '<p class="lcds-block-hint">%s</p>',
            esc_html__('Informations pratiques : renseignez l’étiquette et les entrées dans le panneau de droite.', 'lcds'),
        );
    }

    return;
}

get_template_part('components/block-info', null, $infos);
