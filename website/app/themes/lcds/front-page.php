<?php

/**
 * Page d'accueil.
 *
 * WordPress donne la priorité à ce gabarit sur index.php pour la page d'accueil.
 * Le contenu est encore en dur : c'est un point de départ de mise en page, à
 * remplacer par des champs éditables quand la maquette sera arrêtée.
 *
 * Les arguments des blocs sont préparés ici, avant le HTML : un tableau
 * multi-lignes noyé dans le balisage se fait désaligner par Pint.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$intro_block = [
    'title' => __('Titre de section', 'lcds'),
    'text' => __('Texte de présentation à remplacer. Ce bloc pose la structure deux colonnes : le média à gauche, le texte à droite.', 'lcds'),
];

get_header();
?>

<main id="main-content" class="main-content front-page">
    <?php get_template_part('components/block-text-media', null, $intro_block); ?>
    <?php get_template_part('components/block-media-full'); ?>
</main>

<?php
get_footer();
