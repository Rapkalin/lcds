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
$items = lcds_gallery_items(lcds_sub_field('galerie'));

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
        // Le carrousel des technologies partage le composant et garde, lui, le
        // défilement natif. Seules les galeries sont pilotées par le
        // défilement de la page — celle-ci et celle des conseils.
        'pinned' => true,
        // Pas de flèches : la galerie avance avec le défilement de la page.
        // Leur retrait emporte le glisser-déposer, dont elles étaient
        // l'alternative — voir components/carousel.php.
        'arrows' => false,
    ],
]);
