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

$about = get_field('a_propos', $page_id);
$about = is_array($about) ? $about : [];

WP_CLI::log(sprintf(
    '==> [init] Page « L’équipe » amorcée (ID %d, visuel %s).',
    $page_id,
    ($about['visuel'] ?? '') === '' ? 'absent' : 'posé',
));
