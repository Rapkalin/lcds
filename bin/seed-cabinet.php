<?php

/**
 * Amorçage de la page « Le cabinet » — joué par bin/init.sh via `wp eval-file`.
 *
 * Crée la page, lui pose le gabarit `template-cabinet.php` — c'est ce gabarit,
 * et non l'identifiant d'URL, qui déclenche le groupe de champs — puis garnit
 * ses deux surfaces : la section « nous trouver » et les groupes de visuels.
 *
 * La copie vient de la maquette `CABINET/LCDS_cabinet.pdf`, sauf les deux
 * légendes de visuel qu'elle laisse en lorem ipsum : rédaction à faire par le
 * client — voir readme/contribution.md.
 *
 * LE PLAN RESTE VIDE, et c'est délibéré : la maquette n'y dessine qu'un aplat
 * gris, et le composant rend exactement cet aplat tant qu'aucune image n'est
 * fournie. Y mettre une photo quelconque donnerait une idée fausse du rendu.
 *
 * IDEMPOTENT. Une page déjà en place n'est jamais réécrite : le contenu saisi
 * par un contributeur ne doit pas être écrasé au redémarrage d'un conteneur.
 * Passer `force` en argument POSITIONNEL pour la recréer volontairement :
 * `wp eval-file bin/seed-cabinet.php force`. WP-CLI refuse les options
 * inconnues sur eval-file, un `--force` serait rejeté.
 *
 * @package lcds
 */

if (! defined('WP_CLI')) {
    return;
}

$force = in_array('force', (array) ($args ?? []), true);
$existing = get_page_by_path('le-cabinet');
$page_id = $existing instanceof WP_Post ? (int) $existing->ID : 0;

if (! $force && $page_id > 0 && get_post_status($page_id) === 'publish') {
    WP_CLI::log('==> [init] Page « Le cabinet » déjà en place (ID ' . $page_id . ').');

    return;
}

if ($page_id === 0) {
    $page_id = (int) wp_insert_post([
        'post_type' => 'page',
        'post_title' => 'Le cabinet',
        'post_name' => 'le-cabinet',
        'post_status' => 'publish',
    ]);
}

if ($page_id === 0) {
    WP_CLI::warning('Création de la page « Le cabinet » impossible.');

    return;
}

// C'est le gabarit qui porte la localisation du groupe de champs : sans lui, la
// page s'affiche mais aucun champ n'apparaît en administration.
update_post_meta($page_id, '_wp_page_template', 'template-cabinet.php');

/**
 * Les visuels viennent de `bin/seed-demo.sh`, qui les extrait des PDF de
 * maquette. Sur un environnement où il n'a pas tourné, les champs image
 * restent vides : le rendu se dégrade, l'amorçage ne casse pas.
 */
$media_map = get_option('lcds_demo_media');
$media_map = is_array($media_map) ? $media_map : [];
$media = static fn(string $slot): int|string => isset($media_map[$slot])
    ? (int) $media_map[$slot]
    : '';

$p = static fn(string ...$lines): string => '<p>' . implode('<br />', $lines) . '</p>';

update_field('titre_h1', 'Le cabinet', $page_id);

update_field('nous_trouver', [
    'titre' => 'Nous trouver',
    // Vide à dessein : voir l'en-tête de ce fichier.
    'plan' => '',
    'entrees' => [
        [
            'icone' => 'pin',
            'titre' => 'Adresse du cabinet',
            'surtitre' => 'Cabinet d’orthodontie LCDS',
            'texte' => $p('2 place Saint-Maurice', '38200 Vienne'),
            // La maquette ne porte aucune destination : `#` rend le bouton
            // visible, là où une URL vide ferait disparaître le composant.
            'lien' => ['title' => 'voir le plan', 'url' => '#', 'target' => ''],
        ],
        [
            'icone' => 'bus',
            'titre' => 'Moyens de transport',
            'surtitre' => 'Bus',
            'texte' => $p(
                'Bus - Jardin de Ville (lignes 1, 6, 5, 4 et 7)',
                'Bus - Saint-Maurice (lignes 1, 6, 5, 4 et 7)',
                'Bus - SNCF Brillier (ligne 7)',
            ),
            'lien' => '',
        ],
        [
            'icone' => 'info',
            'titre' => 'Accessibilité',
            'surtitre' => '',
            'texte' => $p('Entrée accessible', 'Parking gratuit'),
            'lien' => '',
        ],
        [
            'icone' => 'clock',
            'titre' => 'Horaires',
            'surtitre' => '',
            'texte' => $p('Du lundi au vendredi : 09:00 - 19:00', 'Le Samedi : 08:00 - 13:00'),
            'lien' => '',
        ],
        [
            'icone' => 'user',
            'titre' => 'Contact',
            'surtitre' => '',
            'texte' => $p('+33 (0) 4 74 78 33 22', 'contact@lacliniquedusourire.com'),
            'lien' => '',
        ],
    ],
], $page_id);

/**
 * Les six groupes de la maquette, dans son ordre, et ses DIX cadres.
 *
 * Les cadres qu'elle laisse en damier sont des emplacements à remplir : ils
 * sont posés ici SANS image. Un cadre vide reste un cadre — c'est ce que la
 * maquette dessine, et c'est ce qui garde le rail continu. Les retirer creusait
 * un trou de 82px entre deux groupes là où l'écart doit valoir 12.
 */
$visual = static fn(string $slot, string $caption = '', string $dot = 'orange'): array => [
    'visuel' => $media($slot),
    'legende' => $caption,
    'puce' => $dot,
];

$lorem = 'Lorem ipsum dolor sit amet, consectetur';

$empty = ['visuel' => '', 'legende' => '', 'puce' => 'orange'];

update_field('groupes', [
    [
        'titre' => 'Les salles de soin',
        'visuels' => [$visual('cabinet-soin'), $empty, $empty],
    ],
    [
        'titre' => 'Les salles d’attente',
        'visuels' => [
            $visual('cabinet-attente-1'),
            $visual('cabinet-attente-2'),
            $visual('cabinet-attente-3'),
        ],
    ],
    [
        'titre' => 'Le parking privé et gratuit',
        'visuels' => [$visual('cabinet-parking', $lorem, 'orange')],
    ],
    [
        'titre' => 'La salle radio',
        'visuels' => [$empty],
    ],
    [
        'titre' => 'Le laboratoire de prothèses',
        'visuels' => [$visual('cabinet-laboratoire', $lorem, 'turquoise')],
    ],
    [
        'titre' => 'La salle PMR',
        'visuels' => [$empty],
    ],
], $page_id);

// ACF rend `false` — et non un tableau vide — pour un répéteur sans ligne :
// deux groupes de la maquette n'ont aucun visuel, et `?? []` ne rattrape pas
// un `false`. Le compte se faisait alors sur un booléen.
$compte = 0;

foreach ((array) get_field('groupes', $page_id) as $groupe) {
    $compte += count(is_array($groupe['visuels'] ?? null) ? $groupe['visuels'] : []);
}

WP_CLI::log(sprintf(
    '==> [init] Page « Le cabinet » amorcée (ID %d, 6 groupes, %d visuels).',
    $page_id,
    $compte,
));
