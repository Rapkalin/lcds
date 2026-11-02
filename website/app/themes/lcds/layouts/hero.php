<?php

/**
 * Section « hero » : visuel pleine largeur et carte d'appel.
 *
 * Ne fait que lire les sous-champs de la rangée et déléguer au composant, qui
 * porte le balisage et la mise en forme. Le titre `h1` n'est PAS ici : c'est un
 * champ de la page, rendu une fois par front-page.php.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

get_template_part('components/hero', null, [
    'image' => lcds_attachment_id(lcds_sub_field('visuel')),
    'video' => lcds_attachment_id(lcds_sub_field('video')),
    'thumbnail' => lcds_attachment_id(lcds_sub_field('carte_vignette')),
    ...lcds_link(lcds_sub_field('carte_lien')),
    'text' => lcds_sub_field_text('carte_texte'),
]);
