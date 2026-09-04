<?php

/**
 * Bloc « LCDS — Hero » : rendu serveur.
 *
 * Ce gabarit ne fait que lire les champs et déléguer au composant `hero`, qui
 * porte le balisage et la mise en forme. Réécrire le markup ici le laisserait
 * divorcer du composant, déjà mesuré au pixel contre la maquette.
 *
 * Le titre h1 est rendu ici et non dans le composant : il appartient à la page,
 * pas au visuel. Il est masqué visuellement — la maquette ne prévoit aucun titre
 * apparent dans le hero — mais reste lu par les moteurs et les lecteurs d'écran.
 * Sans lui la page d'accueil n'aurait aucun h1.
 *
 * Variables fournies par ACF : $block, $is_preview, $post_id.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$titre = lcds_field_text('titre_h1');
$lien = lcds_field('carte_lien');
$lien = is_array($lien) ? $lien : [];

$hero = [
    'image' => lcds_attachment_id(lcds_field('visuel')),
    'thumbnail' => lcds_attachment_id(lcds_field('carte_vignette')),
    'label' => trim((string) ($lien['title'] ?? '')),
    'text' => lcds_field_text('carte_texte'),
    'url' => trim((string) ($lien['url'] ?? '')),
];

$est_vide = $hero['image'] === 0 && $hero['label'] === '' && $hero['text'] === '' && $titre === '';

if ($est_vide && ! empty($is_preview)) {
    printf(
        '<p class="lcds-block-hint">%s</p>',
        esc_html__('Hero : renseignez le visuel et la carte d’appel dans le panneau de droite.', 'lcds'),
    );

    return;
}

if ($titre !== '') {
    printf('<h1 class="screen-reader-text">%s</h1>', esc_html($titre));
}

get_template_part('components/hero', null, $hero);
