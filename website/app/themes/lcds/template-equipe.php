<?php

/**
 * Template Name: L'équipe
 *
 * Page « L'équipe ».
 *
 * Gabarit SÉLECTIONNABLE dans « Attributs de page », et non `page-equipe.php`
 * qui se brancherait sur l'identifiant d'URL : le contributeur peut renommer la
 * page sans que le gabarit lui échappe, et le groupe de champs suit le même
 * critère de localisation.
 *
 * Ce fichier reste DÉCLARATIF : il lit les champs et délègue aux composants de
 * `components/`, qui n'appellent jamais ACF — voir readme/contribution.md.
 *
 * Le `h1` est le titre de la PREMIÈRE SECTION, « À propos de la Clinique du
 * Sourire » : la maquette n'en dessine aucun autre, contrairement au cabinet où
 * le titre de page vit au-dessus des sections. D'où un titre porté par le
 * composant et non par ce fichier.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

// L'identifiant est EXPLICITE : hors de la boucle, `get_the_ID()` peut rendre
// zéro, et une prévisualisation interroge un autre contenu que celui affiché.
$page_id = (int) get_queried_object_id();

$about = lcds_field('a_propos', $page_id);
$about = is_array($about) ? $about : [];

$team = lcds_field('equipe', $page_id);
$team = is_array($team) ? $team : [];

$meet = lcds_field('rencontrez', $page_id);
$meet = is_array($meet) ? $meet : [];
$groups = [];

// `is_array` et non `?? []` : ACF rend `false` pour un répéteur sans ligne, et
// `(array) false` vaut `[false]` — une ligne fantôme.
$rows = is_array($meet['groupes'] ?? null) ? $meet['groupes'] : [];

foreach ($rows as $row) {
    $people = [];

    foreach ((is_array($row['personnes'] ?? null) ? $row['personnes'] : []) as $person) {
        $people[] = [
            'image' => lcds_attachment_id($person['photo'] ?? 0),
            'hover' => lcds_attachment_id($person['photo_survol'] ?? 0),
            'name' => trim((string) ($person['nom'] ?? '')),
            'role' => trim((string) ($person['role'] ?? '')),
            'bio' => (string) ($person['bio'] ?? ''),
        ];
    }

    $groups[] = [
        'label' => trim((string) ($row['etiquette'] ?? '')),
        'dot' => trim((string) ($row['puce'] ?? '')) ?: 'orange',
        'text' => (string) ($row['texte'] ?? ''),
        'people' => $people,
    ];
}
?>

<main id="main-content" class="main-content page-equipe">
    <?php get_template_part('components/block-about', null, [
        'title' => trim((string) ($about['titre'] ?? '')),
        'element' => 'h1',
        'image' => lcds_attachment_id($about['visuel'] ?? 0),
    ]); ?>

    <?php get_template_part('components/block-intro', null, [
        'label' => trim((string) ($team['etiquette'] ?? '')),
        'dot' => trim((string) ($team['puce'] ?? '')) ?: 'turquoise',
        'text' => (string) ($team['texte'] ?? ''),
    ]); ?>

    <?php get_template_part('components/block-team', null, [
        'title' => trim((string) ($meet['titre'] ?? '')),
        'groups' => $groups,
    ]); ?>
</main>

<?php
get_footer();
