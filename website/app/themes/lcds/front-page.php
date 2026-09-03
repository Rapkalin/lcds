<?php

/**
 * Page d'accueil.
 *
 * WordPress donne la priorité à ce gabarit sur index.php pour la page d'accueil.
 *
 * Le contenu vient encore d'ici, en dur : c'est la copie relevée sur les
 * maquettes, en attendant que la source d'édition soit arbitrée. Ces chaînes ne
 * passent PAS par __() — ce n'est pas de l'interface traduisible, c'est du
 * contenu éditorial, qui n'a pas à vivre dans un fichier de traduction.
 *
 * Les arguments des blocs sont préparés ici, avant le HTML : un tableau
 * multi-lignes noyé dans le balisage se fait désaligner par Pint.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$hero_block = [
    'image' => 0,
    'thumbnail' => 0,
    'label' => __('Prendre RDV', 'lcds'),
    'text' => __('Phrase d\'accroche à remplacer.', 'lcds'),
    'url' => '',
];

$intro_block = [
    'label' => 'l’histoire',
    'dot' => 'turquoise',
    'paragraphs' => [
        'Depuis septembre 1986, le Docteur Yann Le Fur et son équipe n’ont cessé de développer la Clinique du Sourire. Après 40 ans, il s’est associé aux praticiens présents depuis plusieurs années afin de faire perdurer le professionnalisme et l’ambiance familiale du cabinet.',
        'Le cabinet est représenté aujourd’hui par le Dr Yann Le Fur, le Dr Sofia Denarié, le Dr Boris Fouquet, le Dr Martin Monteil et le Dr Alice Le Fur.',
    ],
    'cta' => [
        'label' => 'en savoir plus',
        'url' => '#',
    ],
    // Les largeurs sont celles dessinées sur la maquette : la composition du
    // rail est un choix graphique, pas une conséquence du format des photos,
    // qui sont recadrées dans ces cadres.
    'gallery' => [
        'label' => 'Le cabinet en images',
        'items' => [
            ['width' => 892, 'images' => [0]],
            ['width' => 553, 'images' => [0, 0]],
            ['width' => 666, 'images' => [0]],
            ['width' => 503.2, 'images' => [0]],
            ['width' => 36, 'images' => [0]],
        ],
    ],
];

$treatments_block = [
    'label' => 'Les différents traitements',
    'dot' => 'turquoise',
    'items' => [
        ['title' => 'Interceptif'],
        ['title' => 'Gouttières / Aligneurs'],
        [
            'title' => 'Multibagues',
            'open' => true,
            'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec non viverra sem. Suspendisse ut pretium mauris. Vivamus molestie, metus eget rutrum feugiat, dui ligula vulputate tortor, vitae ultrices elit enim in lectus.',
        ],
        ['title' => 'Orthèses pour l’apnée du sommeil'],
        ['title' => 'Protège-dents'],
    ],
    'cta' => [
        'label' => 'voir tous les traitements',
        'url' => '#',
    ],
];

get_header();
?>

<main id="main-content" class="main-content front-page">
    <?php get_template_part('components/hero', null, $hero_block); ?>
    <?php get_template_part('components/block-intro', null, $intro_block); ?>
    <?php get_template_part('components/block-treatments', null, $treatments_block); ?>
</main>

<?php
get_footer();
