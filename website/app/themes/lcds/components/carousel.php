<?php

/**
 * Carrousel horizontal : un rail défilable et ses contrôles.
 *
 * Le défilement est NATIF (`overflow-x`) : le geste tactile, le pavé tactile, la
 * molette horizontale et le clavier fonctionnent sans une ligne de JavaScript.
 * Les boutons et l'indicateur ne sont qu'une surcouche.
 *
 * Arguments (via get_template_part) :
 *   items array   Une entrée par élément du rail :
 *                   width  int   Largeur en pixels, telle que dessinée.
 *                   images array Un identifiant d'attachement, ou deux pour une
 *                                colonne empilée.
 *   label string  Libellé accessible du rail.
 *
 * Les éléments sont normalisés AVANT le balisage : une instruction PHP au milieu
 * du HTML se fait désaligner par Pint (`statement_indentation`).
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$items = isset($args['items']) && is_array($args['items']) ? $args['items'] : [];
$label = isset($args['label']) ? (string) $args['label'] : '';

if ($items === []) {
    return;
}

$rows = [];

foreach ($items as $item) {
    $images = isset($item['images']) && is_array($item['images']) ? $item['images'] : [];
    $medias = [];

    foreach ($images as $image) {
        $medias[] = $image === 0 ? '' : lcds_render_image($image, [
            'class' => 'carousel__image',
            'alt' => '',
        ], 'large');
    }

    $rows[] = [
        'width' => isset($item['width']) ? (float) $item['width'] : 0.0,
        // Un élément sans visuel garde tout de même son cadre : l'aplat tient la
        // place tant que la médiathèque n'est pas remplie.
        'medias' => $medias === [] ? [''] : $medias,
    ];
}
?>

<div class="carousel" data-carousel>
    <ul class="carousel__rail" tabindex="0" role="group" aria-label="<?php echo esc_attr($label); ?>">
        <?php foreach ($rows as $row) : ?>
            <li class="carousel__item" style="--item-width: <?php echo esc_attr((string) $row['width']); ?>px">
                <?php foreach ($row['medias'] as $media) : ?>
                    <div class="carousel__media"><?php echo $media; ?></div>
                <?php endforeach; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="carousel__controls">
        <div class="carousel__track">
            <span class="carousel__thumb" data-carousel-thumb></span>
        </div>

        <div class="carousel__buttons">
            <button class="carousel__button" type="button" data-carousel-prev>
                <?php get_template_part('components/icon-arrow'); ?>
                <span class="screen-reader-text"><?php esc_html_e('Précédent', 'lcds'); ?></span>
            </button>
            <button class="carousel__button carousel__button--next" type="button" data-carousel-next>
                <?php get_template_part('components/icon-arrow'); ?>
                <span class="screen-reader-text"><?php esc_html_e('Suivant', 'lcds'); ?></span>
            </button>
        </div>
    </div>
</div>
