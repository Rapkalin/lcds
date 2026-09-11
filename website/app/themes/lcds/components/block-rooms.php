<?php

/**
 * Groupe de visuels : un titre à gauche, une pile de visuels à droite.
 *
 * SIX groupes de la page du cabinet partagent ce seul motif — les salles de
 * soin, celles d'attente, le parking, la salle radio, le laboratoire et la
 * salle PMR. Ils ne diffèrent que par leur titre et par le nombre de visuels.
 *
 * Les groupes s'enchaînent sans respiration : le rail des visuels est CONTINU
 * d'un groupe à l'autre, avec le même écart de 12 partout — relevé au pixel.
 * C'est la feuille de style qui s'en charge, pas ce fichier.
 *
 * Arguments (via get_template_part) :
 *   title   string Titre du groupe, qui le nomme dans le plan de la page.
 *   visuals array  Un visuel par entrée :
 *                    image   int    Identifiant d'attachement.
 *                    caption string Légende posée sur le visuel. Facultative :
 *                                   deux visuels sur dix en portent une.
 *                    dot     string Couleur de la puce de la légende.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$title = isset($args['title']) ? (string) $args['title'] : '';
$visuals = isset($args['visuals']) && is_array($args['visuals']) ? $args['visuals'] : [];

$rows = [];

foreach ($visuals as $visual) {
    $image = isset($visual['image']) ? (int) $visual['image'] : 0;

    // Sans visuel il n'y a rien à montrer : la maquette dessine des cadres
    // vides, mais ce sont des emplacements à remplir, pas un état à rendre.
    if ($image === 0) {
        continue;
    }

    $rows[] = [
        'image' => $image,
        'caption' => isset($visual['caption']) ? trim((string) $visual['caption']) : '',
        'dot' => isset($visual['dot']) ? (string) $visual['dot'] : 'orange',
    ];
}

if ($rows === [] && $title === '') {
    return;
}

// Voir block-intro : une `<section>` sans nom accessible n'est pas exposée
// comme région, et `wp_unique_id` évite la collision entre les six groupes.
$headingId = $title === '' ? '' : wp_unique_id('section-titre-');
?>

<section class="block-rooms"<?php echo $headingId === '' ? '' : ' aria-labelledby="' . esc_attr($headingId) . '"'; ?>>
    <div class="block-rooms__inner">
        <?php if ($title !== '') : ?>
            <h2 class="block-rooms__title" id="<?php echo esc_attr($headingId); ?>"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <?php if ($rows !== []) : ?>
            <ul class="block-rooms__list">
                <?php foreach ($rows as $row) : ?>
                    <li class="block-rooms__item">
                        <?php echo lcds_render_image($row['image'], ['class' => 'block-rooms__image'], 'large'); ?>

                        <?php if ($row['caption'] !== '') : ?>
                            <div class="block-rooms__caption">
                                <?php get_template_part('components/tag', null, [
                                    'label' => $row['caption'],
                                    'dot' => $row['dot'],
                                    'media' => true,
                                ]); ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
