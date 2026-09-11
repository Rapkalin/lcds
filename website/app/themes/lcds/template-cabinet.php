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
 * Le titre `h1` est rendu MASQUÉ visuellement : la maquette n'en dessine aucun,
 * comme sur la page d'accueil. Conforme au RGAA, qui porte sur la structure des
 * titres et non sur leur visibilité, mais c'est un manque à soulever avec le
 * designer — voir readme/accessibilite.md.
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
    $visuals = [];

    foreach ((array) ($group['visuels'] ?? []) as $visual) {
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
        <h1 class="screen-reader-text"><?php echo esc_html($heading); ?></h1>
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
