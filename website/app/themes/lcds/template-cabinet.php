<?php

/**
 * Template Name: Le cabinet
 *
 * Page « Le cabinet ».
 *
 * Gabarit SÉLECTIONNABLE dans « Attributs de page », et non `page-cabinet.php`
 * qui se brancherait sur l'identifiant d'URL : le contributeur peut renommer la
 * page sans que le gabarit lui échappe, et le groupe de champs suit le même
 * critère de localisation.
 *
 * Ce fichier reste DÉCLARATIF : il lit les champs et délègue aux composants de
 * `components/`, qui n'appellent jamais ACF — voir readme/contribution.md.
 *
 * Le titre `h1` est VISIBLE, contrairement à celui de l'accueil : la maquette
 * le dessine en tête de page depuis que la première section ne porte plus
 * qu'une pastille. Il est rendu à la taille des `h2` — relevé au pixel, même
 * chasse de 28,4 par caractère dans les deux cas.
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
$heading = trim((string) lcds_field('titre_h1', $page_id));

$locate = lcds_field('nous_trouver', $page_id);
$locate = is_array($locate) ? $locate : [];
$rows = isset($locate['entrees']) && is_array($locate['entrees']) ? $locate['entrees'] : [];
$entries = [];

foreach ($rows as $row) {
    $link = is_array($row['lien'] ?? null) ? $row['lien'] : [];

    $entries[] = [
        'icon' => (string) ($row['icone'] ?? ''),
        'title' => trim((string) ($row['titre'] ?? '')),
        'overline' => trim((string) ($row['surtitre'] ?? '')),
        'text' => (string) ($row['texte'] ?? ''),
        'cta' => [
            'label' => trim((string) ($link['title'] ?? '')),
            'url' => trim((string) ($link['url'] ?? '')),
        ],
    ];
}

$groups = [];

foreach ((array) lcds_field('groupes', $page_id) as $group) {
    if (! is_array($group)) {
        continue;
    }

    $visuals = [];

    // `is_array` et non `?? []` : ACF rend `false` pour un répéteur sans
    // ligne, et `(array) false` vaut `[false]` — une ligne fantôme.
    $rows = is_array($group['visuels'] ?? null) ? $group['visuels'] : [];

    foreach ($rows as $visual) {
        $visuals[] = [
            'image' => lcds_attachment_id($visual['visuel'] ?? 0),
            'caption' => trim((string) ($visual['legende'] ?? '')),
            'dot' => trim((string) ($visual['puce'] ?? '')) ?: 'orange',
        ];
    }

    $groups[] = [
        'title' => trim((string) ($group['titre'] ?? '')),
        'visuals' => $visuals,
    ];
}
?>

<main id="main-content" class="main-content page-cabinet">
    <?php if ($heading !== '') : ?>
        <h1 class="page-cabinet__title"><?php echo esc_html($heading); ?></h1>
    <?php endif; ?>

    <?php get_template_part('components/block-locate', null, [
        'title' => trim((string) ($locate['titre'] ?? '')),
        'image' => lcds_attachment_id($locate['plan'] ?? 0),
        'entries' => $entries,
    ]); ?>

    <?php foreach ($groups as $group) : ?>
        <?php get_template_part('components/block-rooms', null, $group); ?>
    <?php endforeach; ?>
</main>

<?php
get_footer();
