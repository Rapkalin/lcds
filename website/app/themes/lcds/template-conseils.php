<?php

/**
 * Template Name: Conseils
 *
 * Page « Conseils ».
 *
 * Gabarit SÉLECTIONNABLE dans « Attributs de page », et non `page-conseils.php`
 * qui se brancherait sur l'identifiant d'URL : le contributeur peut renommer la
 * page sans que le gabarit lui échappe, et le groupe de champs suit le même
 * critère de localisation.
 *
 * Les sections sont les layouts d'un champ de contenu flexible : le
 * contributeur les ajoute, les réordonne et les supprime au glisser-déposer,
 * comme sur l'accueil. Elles ne sont que DEUX — accordéon et description — et
 * elles partagent la même carcasse, d'où un seul composant et non deux
 * gabarits dans `layouts/`, qui est le catalogue de l'accueil et de personne
 * d'autre (voir tests/Unit/HomepageLayoutsTest.php).
 *
 * Le `h1` est MASQUÉ VISUELLEMENT, comme sur l'accueil : la maquette ne dessine
 * aucun titre de page, elle commence directement par le titre de la première
 * section. Sans lui la page n'aurait aucun `h1`, et le faire porter par la
 * première section donnerait « Les urgences en orthodontie » pour titre à une
 * page qui s'appelle « Conseils ».
 *
 * `lcds_has_acf()` garde la boucle : ACF est un plugin sous licence, absent du
 * dépôt et installé à la main. Sans cette garde, un environnement où il n'est
 * pas actif renvoie une page blanche en 500 au lieu d'une page sans sections.
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
?>

<main id="main-content" class="main-content page-conseils">
    <?php if ($heading !== '') : ?>
        <h1 class="screen-reader-text"><?php echo esc_html($heading); ?></h1>
    <?php endif; ?>

    <?php if (lcds_has_acf() && have_rows('sections', $page_id)) : ?>
        <?php while (have_rows('sections', $page_id)) : ?>
            <?php
            the_row();

            // Allow-list : le nom du layout vient de la base. Inconnu, la
            // section n'est pas rendue — plutôt que d'en rendre une autre.
            $kind = LcdsAdviceSection::fromLayout(get_row_layout());

            if ($kind === null) {
                continue;
            }

            $groups = [];

            // `is_array` et non `?? []` : ACF rend `false` pour un répéteur
            // sans ligne, et `(array) false` vaut `[false]` — une ligne
            // fantôme.
            $rows = lcds_sub_field('groupes');
            $rows = is_array($rows) ? $rows : [];

            foreach ($rows as $row) {
                $items = [];

                foreach ((is_array($row['questions'] ?? null) ? $row['questions'] : []) as $item) {
                    $title = trim((string) ($item['titre'] ?? ''));

                    if ($title === '') {
                        continue;
                    }

                    $items[] = [
                        'title' => $title,
                        'text' => (string) ($item['reponse'] ?? ''),
                        'open' => ! empty($item['ouvert']),
                        // `wp_unique_id` et non un compteur : la page porte
                        // plusieurs accordéons, et `aria-controls` ne tolère
                        // pas deux fois le même identifiant.
                        'id' => wp_unique_id('conseil-'),
                    ];
                }

                $link = is_array($row['lien'] ?? null) ? $row['lien'] : [];

                $groups[] = [
                    'label' => trim((string) ($row['etiquette'] ?? '')),
                    'dot' => trim((string) ($row['puce'] ?? '')) ?: 'orange',
                    'heading' => trim((string) ($row['titre'] ?? '')),
                    'text' => (string) ($row['texte'] ?? ''),
                    'items' => $items,
                    'cta' => [
                        'label' => trim((string) ($link['title'] ?? '')),
                        'url' => trim((string) ($link['url'] ?? '')),
                    ],
                ];
            }

            get_template_part('components/block-advice', null, [
                'title' => lcds_sub_field_text('titre'),
                'kind' => $kind->value,
                'intro' => (string) (lcds_sub_field('chapo') ?? ''),
                'groups' => $groups,
            ]);
            ?>
        <?php endwhile; ?>
    <?php endif; ?>
</main>

<?php
get_footer();
