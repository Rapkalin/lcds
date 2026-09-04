<?php

/**
 * Bloc « LCDS — Texte et galerie » : rendu serveur.
 *
 * Ne fait que lire les champs et déléguer au composant `block-intro`, qui porte
 * le balisage et la mise en forme.
 *
 * Les largeurs du rail viennent de LcdsMediaShape : le contributeur choisit une
 * forme nommée, jamais un nombre de pixels.
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
$rows = lcds_field('galerie');
$items = [];

foreach ((is_array($rows) ? $rows : []) as $row) {
    $shape = LcdsMediaShape::fromValue($row['forme'] ?? null, LcdsMediaShape::Medium);
    $images = [lcds_attachment_id($row['image'] ?? null)];

    if ($shape->isPair()) {
        $images[] = lcds_attachment_id($row['image_2'] ?? null);
    }

    $items[] = ['width' => $shape->width(), 'images' => $images];
}

$intro = [
    'label' => lcds_field_text('etiquette'),
    'dot' => lcds_field_text('puce') ?: 'turquoise',
    'text' => (string) lcds_field('texte'),
    'cta' => [
        'label' => trim((string) ($cta['title'] ?? '')),
        'url' => trim((string) ($cta['url'] ?? '')),
    ],
    'gallery' => $items === [] ? [] : [
        'label' => lcds_field_text('galerie_libelle') ?: __('Galerie', 'lcds'),
        'items' => $items,
    ],
];

if ($intro['label'] === '' && $intro['text'] === '' && $items === []) {
    if (! empty($is_preview)) {
        printf(
            '<p class="lcds-block-hint">%s</p>',
            esc_html__('Texte et galerie : renseignez l’étiquette, le texte et les visuels dans le panneau de droite.', 'lcds'),
        );
    }

    return;
}

get_template_part('components/block-intro', null, $intro);
