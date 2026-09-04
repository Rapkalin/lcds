<?php

/**
 * Carrousel horizontal : un rail défilable et ses contrôles.
 *
 * Le défilement est NATIF (`overflow-x`) : le geste tactile, le pavé tactile, la
 * molette horizontale et le clavier fonctionnent sans une ligne de JavaScript.
 * Les boutons et l'indicateur ne sont qu'une surcouche.
 *
 * Arguments (via get_template_part) :
 *   items  array  Une entrée par élément du rail :
 *                    width   int    Largeur en pixels, telle que dessinée.
 *                    images  array  Un identifiant d'attachement, ou deux pour
 *                                   une colonne empilée. Ignoré si `content`
 *                                   est fourni.
 *                    content string Balisage déjà produit, pour un rail qui ne
 *                                   porte pas de simples visuels. C'est ce qui
 *                                   permet au carrousel Technologies de
 *                                   réutiliser CE rail et CES contrôles — la
 *                                   maquette les dessine strictement
 *                                   identiques, à écrire une fois.
 *                    tilt    float  Inclinaison en degrés. La maquette fait
 *                                   onduler les cartes Technologies : +2,88 / 0
 *                                   / -2,88, mesuré sur le PDF.
 *   label  string Libellé accessible du rail.
 *   height int    Hauteur du rail en pixels. 629 pour la galerie d'intro, 494
 *                 pour les cartes inclinées — voir readme/front.md.
 *   modifier string Suffixe de classe posé sur le carrousel.
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

// Le rail est focalisable pour être défilable au clavier : il lui faut donc un
// nom. Un libellé vidé par un contributeur laisserait `aria-label=""`, que les
// navigateurs traitent comme absent — d'où ce repli.
$label = isset($args['label']) ? trim((string) $args['label']) : '';
$label = $label === '' ? __('Galerie de visuels', 'lcds') : $label;

if ($items === []) {
    return;
}

$height = isset($args['height']) ? (float) $args['height'] : 629.0;
$modifier = isset($args['modifier']) ? (string) $args['modifier'] : '';
$rows = [];

foreach ($items as $item) {
    $content = isset($item['content']) ? (string) $item['content'] : '';
    $medias = [];

    if ($content === '') {
        foreach ((isset($item['images']) && is_array($item['images']) ? $item['images'] : []) as $image) {
            // Pas d'`alt` imposé : il vient de la médiathèque. Une image sans
            // alternative saisie est rendue décorative par WordPress, ce qui
            // reste la décision du contributeur — voir readme/images.md.
            $medias[] = $image === 0 ? '' : lcds_render_image($image, [
                'class' => 'carousel__image',
            ], 'large');
        }

        // Un élément sans visuel garde tout de même son cadre : l'aplat tient
        // la place tant que la médiathèque n'est pas remplie.
        $medias = $medias === [] ? [''] : $medias;
    }

    $rows[] = [
        'width' => isset($item['width']) ? (float) $item['width'] : 0.0,
        'tilt' => isset($item['tilt']) ? (float) $item['tilt'] : 0.0,
        'content' => $content,
        'medias' => $medias,
    ];
}
?>

<div class="carousel<?php echo $modifier === '' ? '' : ' carousel--' . esc_attr($modifier); ?>" data-carousel style="--rail-height: <?php echo esc_attr((string) $height); ?>px">
    <?php /* Pas de role="group" : il écrasait le rôle `list` du <ul>, et le nombre de visuels n'était plus annoncé. */ ?>
    <ul class="carousel__rail" tabindex="0" aria-label="<?php echo esc_attr($label); ?>">
        <?php foreach ($rows as $row) : ?>
            <li class="carousel__item" style="--item-width: <?php echo esc_attr((string) $row['width']); ?>px; --item-tilt: <?php echo esc_attr((string) $row['tilt']); ?>deg">
                <?php if ($row['content'] !== '') : ?>
                    <?php echo $row['content']; ?>
                <?php else : ?>
                    <?php foreach ($row['medias'] as $media) : ?>
                        <div class="carousel__media"><?php echo $media; ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
