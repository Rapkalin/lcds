<?php

/**
 * Amorçage de la page « L'équipe » — joué par bin/init.sh via `wp eval-file`.
 *
 * Crée la page, lui pose le gabarit `template-equipe.php` — c'est ce gabarit,
 * et non l'identifiant d'URL, qui déclenche le groupe de champs — puis garnit
 * ses deux premières sections : « à propos » et « l'équipe ».
 *
 * La copie vient de la maquette `EQUIPE/LCDS_equipe.pdf`. Le texte de
 * présentation y est du LOREM IPSUM : il est repris tel quel, la rédaction est
 * à faire par le client — même parti pris que les légendes du cabinet, voir
 * readme/contribution.md.
 *
 * IDEMPOTENT. Une page déjà en place n'est jamais réécrite : le contenu saisi
 * par un contributeur ne doit pas être écrasé au redémarrage d'un conteneur.
 * Passer `force` en argument POSITIONNEL pour la recréer volontairement :
 * `wp eval-file bin/seed-equipe.php force`. WP-CLI refuse les options
 * inconnues sur eval-file, un `--force` serait rejeté.
 *
 * @package lcds
 */

if (! defined('WP_CLI')) {
    return;
}

$force = in_array('force', (array) ($args ?? []), true);
$existing = get_page_by_path('l-equipe');
$page_id = $existing instanceof WP_Post ? (int) $existing->ID : 0;

if (! $force && $page_id > 0 && get_post_status($page_id) === 'publish') {
    WP_CLI::log('==> [init] Page « L’équipe » déjà en place (ID ' . $page_id . ').');

    return;
}

if ($page_id === 0) {
    $page_id = (int) wp_insert_post([
        'post_type' => 'page',
        'post_title' => 'L’équipe',
        'post_name' => 'l-equipe',
        'post_status' => 'publish',
    ]);
}

if ($page_id === 0) {
    WP_CLI::warning('Création de la page « L’équipe » impossible.');

    return;
}

// C'est le gabarit qui porte la localisation du groupe de champs : sans lui, la
// page s'affiche mais aucun champ n'apparaît en administration.
update_post_meta($page_id, '_wp_page_template', 'template-equipe.php');

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

update_field('a_propos', [
    'titre' => 'À propos de la Clinique du Sourire',
    'visuel' => $media('equipe-groupe'),
], $page_id);

update_field('equipe', [
    'etiquette' => 'l’équipe',
    // Relevé sur la maquette : la puce vaut rgb(4, 139, 140), le turquoise du
    // système — et non l'orange de la pastille du cabinet.
    'puce' => 'turquoise',
    'texte' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. '
        . 'Proin nec sapien turpis. Nunc consequat sit amet odio vitae vehicula. '
        . 'Mauris vitae hendrerit ipsum. Donec volutpat volutpat efficitur. '
        . 'Nulla leo sapien, suscipit in mi ac, ornare sollicitudin arcu. '
        . 'Sed id hendrerit odio. In varius vehicula magna, eget ultricies '
        . 'tellus scelerisque eu.</p>',
], $page_id);

/**
 * Les cinq groupes de la maquette, dans son ordre, et ses VINGT-NEUF cartes.
 *
 * Douze portent une photo, dix-sept non : ce sont les emplacements que la
 * maquette laisse en damier, et le composant les rend comme elle — un cadre
 * vide reste un cadre.
 *
 * L'APPARIEMENT PERSONNE ↔ PHOTO EST ARBITRAIRE. La maquette ne nomme que six
 * personnes sur vingt-neuf, les autres sont du lorem ipsum, et elle réutilise
 * la même photo d'une carte à l'autre. Ce qui est fidèle, c'est QUELS
 * emplacements portent une photo, pas laquelle.
 */
$lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec non '
    . 'viverra sem. Suspendisse ut pretium mauris. Vivamus molestie, metus eget '
    . 'rutrum feugiat, dui ligula vulputate tortor, vitae ultrices elit enim in '
    . 'lectus.';

$personne = static fn(string $nom, string $role, string $slot = ''): array => [
    'photo' => $slot === '' ? '' : $media($slot),
    'nom' => $nom,
    'role' => $role,
    // La maquette dessine le bouton « + » sur CHAQUE carte, sans jamais montrer
    // ce qu'il ouvre. Le texte est donc semé partout pour que le bouton soit là
    // — l'ouverture, elle, reste à dessiner.
    'bio' => '<p>' . $lorem . '</p>',
];

$assistantes = [
    $personne('Lorem ipsum', 'assistante coordinatrice', 'equipe-p05'),
    $personne('Lorem ipsum', 'assistante', 'equipe-p06'),
    $personne('Lorem ipsum', 'assistante', 'equipe-p07'),
    $personne('Lorem ipsum', 'assistante', 'equipe-p08'),
    $personne('Lorem ipsum', 'assistante', 'equipe-p09'),
    $personne('Lorem ipsum', 'assistante', 'equipe-p10'),
    $personne('Lorem ipsum', 'assistante', 'equipe-p11'),
];

for ($i = 0; $i < 8; $i++) {
    $assistantes[] = $personne('Lorem ipsum', 'assistante');
}

update_field('rencontrez', [
    'titre' => 'Rencontrez l’équipe',
    'groupes' => [
        [
            'etiquette' => 'les docteurs',
            'puce' => 'orange',
            'texte' => '<p>' . $lorem . '</p>',
            'personnes' => [
                $personne('Yann Le Fur', 'Orthodontiste', 'equipe-p01'),
                $personne('Sofia Denarie', 'Orthodontiste'),
                $personne('Martin Monteil', 'Orthodontiste', 'equipe-p02'),
                $personne('Boris Fouquet', 'Orthodontiste', 'equipe-p03'),
                $personne('Alice Le Fur', 'Orthodontiste', 'equipe-p04'),
            ],
        ],
        [
            'etiquette' => 'le secrétariat',
            'puce' => 'orange',
            'texte' => '<p>' . $lorem . '</p>',
            'personnes' => [
                $personne('Stéphanie', 'secrétaire coordinatrice'),
                $personne('Sandrine', 'secrétaire'),
            ],
        ],
        [
            'etiquette' => 'les assistantes',
            'puce' => 'orange',
            'texte' => '<p>' . $lorem . '</p>',
            'personnes' => $assistantes,
        ],
        [
            'etiquette' => 'l’équipe labo',
            'puce' => 'orange',
            'texte' => '<p>' . $lorem . '</p>',
            'personnes' => [
                $personne('Mélanie', 'responsable labo'),
                $personne('Mathieu', 'Lorem ipsum', 'equipe-p12'),
                $personne('Karine', 'Lorem ipsum'),
                $personne('Elsa', 'Lorem ipsum'),
                $personne('Wilfried', 'Lorem ipsum'),
            ],
        ],
        [
            'etiquette' => 'les techniciennes de surface',
            'puce' => 'orange',
            'texte' => '<p>' . $lorem . '</p>',
            'personnes' => [
                $personne('Élisa', 'Lorem ipsum'),
                $personne('Elsa', 'Lorem ipsum'),
            ],
        ],
    ],
], $page_id);

$about = get_field('a_propos', $page_id);
$about = is_array($about) ? $about : [];

$meet = get_field('rencontrez', $page_id);
$meet = is_array($meet) ? $meet : [];
$personnes = 0;

foreach ((is_array($meet['groupes'] ?? null) ? $meet['groupes'] : []) as $groupe) {
    $personnes += count(is_array($groupe['personnes'] ?? null) ? $groupe['personnes'] : []);
}

WP_CLI::log(sprintf(
    '==> [init] Page « L’équipe » amorcée (ID %d, visuel %s, %d groupes, %d personnes).',
    $page_id,
    ($about['visuel'] ?? '') === '' ? 'absent' : 'posé',
    count(is_array($meet['groupes'] ?? null) ? $meet['groupes'] : []),
    $personnes,
));
