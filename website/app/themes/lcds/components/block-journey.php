<?php

/**
 * Section « le parcours de soin » : un slider, une étape par vue.
 *
 * Le rail, les flèches, la barre et le glisser à la souris sont ceux de
 * `components/carousel`, avec une étape pleine largeur par élément : une page
 * de défilement vaut alors exactement une étape. La section ne pilote plus le
 * défilement de la page.
 *
 * Arguments (via get_template_part) :
 *   label string  Libellé de l'étiquette.
 *   dot   string  Couleur de la puce de l'étiquette.
 *   steps array   Une entrée par étape :
 *                   title    string  Titre de l'étape.
 *                   text     string  Contenu riche : paragraphes et listes.
 *                   duration string  Durée indicative, facultative.
 *                   image    int     Visuel, identifiant d'attachement.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$label = isset($args['label']) ? (string) $args['label'] : '';
$dot = isset($args['dot']) ? (string) $args['dot'] : 'orange';
$steps = isset($args['steps']) && is_array($args['steps']) ? $args['steps'] : [];

$items = [];

foreach (array_values($steps) as $index => $step) {
    $title = isset($step['title']) ? (string) $step['title'] : '';

    if ($title === '') {
        continue;
    }

    $image = isset($step['image']) ? (int) $step['image'] : 0;

    $items[] = [
        'content' => lcds_capture('components/journey-step', [
            'number' => str_pad((string) (count($items) + 1), 2, '0', STR_PAD_LEFT),
            'title' => $title,
            'text' => isset($step['text']) ? (string) $step['text'] : '',
            'duration' => isset($step['duration']) ? (string) $step['duration'] : '',
            'media' => $image === 0 ? '' : lcds_render_image($image, ['class' => 'journey__image'], 'large'),
        ]),
    ];
}

if ($items === []) {
    return;
}

// Voir block-treatments : une `<section>` sans nom accessible n'est pas exposée
// comme région, et `wp_unique_id` évite la collision entre deux sections.
$headingId = $label === '' ? '' : wp_unique_id('section-titre-');
?>

<section class="journey"<?php echo $headingId === '' ? '' : ' aria-labelledby="' . esc_attr($headingId) . '"'; ?>>
    <div class="journey__head">
        <div class="journey__label">
            <?php get_template_part('components/tag', null, ['label' => $label, 'dot' => $dot, 'element' => 'h2', 'id' => $headingId]); ?>
        </div>
    </div>

    <?php get_template_part('components/carousel', null, [
        'items' => $items,
        'label' => $label === '' ? __('Étapes du parcours de soin', 'lcds') : $label,
        'modifier' => 'journey',
        'paged' => true,
    ]); ?>
</section>
