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
 * Ils passent ensuite par le filtre `lcds_front_page_blocks`. C'est la couture
 * par laquelle la future source d'édition viendra les remplir — et, en attendant,
 * par laquelle un outil local peut y injecter des visuels de démonstration sans
 * que ce gabarit n'en sache rien.
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
    'url' => '#',
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

$journey_block = [
    'label' => 'le parcours de soin',
    'dot' => 'orange',
    'steps' => [
        [
            'title' => 'Première consultation',
            'text' => [
                'Ce premier contact a pour but de faire votre connaissance, de définir vos attentes et de déterminer les objectifs de traitement réalisables en fonction de votre situation clinique actuelle.',
                'Vous pourrez exprimer le motif de votre venue et l’orthodontiste réalisera un premier examen clinique qui permettra de déterminer si un traitement orthodontique est indiqué.',
                'Si un traitement est envisagé, nous vous proposerons un rendez-vous afin de réaliser le bilan orthodontique, indispensable pour détailler le diagnostic et les possibilités de traitement ainsi que la durée approximative de celui-ci.',
            ],
            'images' => [
                ['width' => 327, 'image' => 0],
                ['width' => 214, 'image' => 0],
            ],
        ],
        [
            'title' => 'Bilan orthodontique',
            'duration' => '30 min',
            'text' => [
                'Le bilan permettra de préciser le diagnostic, les objectifs thérapeutiques, le type d’appareillage le plus indiqué et la durée du traitement.',
                'Lors de ce bilan, nous réaliserons :',
                [
                    'Des photos du visage et intra-buccale',
                    'Des empreintes numériques pour réaliser un moulage d’étude',
                    'Des clichés radiographiques (panoramique dentaire et téléradiographie de profil/face)',
                ],
            ],
            'images' => [
                ['width' => 214, 'image' => 0],
                ['width' => 327, 'image' => 0],
            ],
        ],
        [
            'title' => 'Compte-rendu',
            'duration' => '45 min',
            'text' => [
                'Au terme du bilan orthodontique, l’analyse des différents éléments du dossier orthodontique permettent d’établir un plan de traitement précis, qui sera donc abordé au rendez-vous de compte-rendu. Des documents vous seront fournis également (le devis précis comportant la durée du traitement et le type d’appareillage choisit ainsi que la demande d’entente préalable pour les patients de moins de 16 ans).',
            ],
            'images' => [
                ['width' => 327, 'image' => 0],
            ],
        ],
        [
            'title' => 'Pose de l’appareillage',
            'text' => [
                'Pour l’appareil multi-attaches, l’orthodontiste place un écarteur afin d’écarter les lèvres et avoir une bonne visibilité sur les dents. Après un nettoyage des dents, un gel est appliqué sur chaque dent afin de les rendre un peu rugueuse, ce qui permettra à la colle qui fixe les brackets de mieux s’accrocher. Puis chaque bracket sera collé sur les dents, qui seront ensuite reliés par un fil.',
            ],
            'images' => [
                ['width' => 214, 'image' => 0],
                ['width' => 327, 'image' => 0],
            ],
        ],
        [
            'title' => 'Rendez-vous de suivi de contrôle et d’activations',
            'text' => [
                'Ce sont des rendez-vous de routine qui se répèteront environ toutes les 5 à 10 semaines. C’est lors de ces rendez-vous que l’orthodontiste change l’arc, effectue des activations d’autres appareils.',
            ],
            'images' => [
                ['width' => 214, 'image' => 0],
                ['width' => 327, 'image' => 0],
            ],
        ],
        [
            'title' => 'Dépose de l’appareil et contention',
            'text' => [
                'C’est lors de ce rendez-vous que l’appareil est enlevé. Ce n’est pas pour ça que le traitement s’arrête brutalement, bien au contraire.',
                'Le travail de stabilisation du résultat commence avec la mise en place d’une contention permanente ou amovible (fil de contention collé sur la face interne des dents et/ou gouttière en port nocturne)',
                'La contention est obligatoire après le traitement d’orthodontie, surtout la première année suivant la dépose de votre appareil.',
                'Après l’alignement, les dents ont toujours tendance à revenir en position initiale. De plus, les dents bougent tout au long de la vie sous pression de la langue, des lèvres, des joues et elles se déplacent naturellement vers l’avant avec l’âge, d’où l’importance de la contention.',
            ],
        ],
    ],
];

/**
 * Un filtre peut retirer une clé : chaque bloc retombe alors sur un tableau vide,
 * donc sur ses valeurs de repli, plutôt que sur une erreur.
 *
 * @var array $blocks
 */
$blocks = apply_filters('lcds_front_page_blocks', [
    'hero' => $hero_block,
    'intro' => $intro_block,
    'treatments' => $treatments_block,
    'journey' => $journey_block,
]);

$hero_block = $blocks['hero'] ?? [];
$intro_block = $blocks['intro'] ?? [];
$treatments_block = $blocks['treatments'] ?? [];
$journey_block = $blocks['journey'] ?? [];

get_header();
?>

<main id="main-content" class="main-content front-page">
    <?php get_template_part('components/hero', null, $hero_block); ?>
    <?php get_template_part('components/block-intro', null, $intro_block); ?>
    <?php get_template_part('components/block-treatments', null, $treatments_block); ?>
    <?php get_template_part('components/block-journey', null, $journey_block); ?>
</main>

<?php
get_footer();
