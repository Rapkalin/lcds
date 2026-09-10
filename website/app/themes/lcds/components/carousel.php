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
 *   arrows   bool  Rendre les deux flèches de navigation. `true` par défaut.
 *                  `false` retire AUSSI le glisser-déposer à la souris : les
 *                  flèches sont l'alternative au geste qu'exige le WCAG 2.5.7,
 *                  et un geste sans alternative ne doit pas exister. Le script
 *                  le déduit de leur absence, il n'y a rien d'autre à passer.
 *   pinned   bool  Le rail est-il piloté par le défilement vertical de la page ?
 *                  Déclaré par la SECTION qui compose le carrousel, jamais
 *                  deviné d'un sélecteur parent : le composant sert aussi la
 *                  section Technologies, qui n'en veut pas. Et ce n'est pas un
 *                  champ ACF — c'est une décision de conception, pas un choix
 *                  de contribution.
 *
 *                  L'attribut ne fait qu'AUTORISER l'effet. C'est le script qui
 *                  l'active, et lui seul : sans JavaScript, sous
 *                  `prefers-reduced-motion` ou sur un rail qui tient dans la
 *                  vue, le carrousel reste celui de partout ailleurs.
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
$isPinned = ! empty($args['pinned']);
$hasArrows = ! isset($args['arrows']) || (bool) $args['arrows'];
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

<?php if ($isPinned) : ?>
    <?php
    /*
     * Réserve de défilement de l'épinglage. Elle vaut la course horizontale du
     * rail, publiée par le script : celle-ci dépend des largeurs choisies par
     * le contributeur ET de la largeur de vue, aucun calcul CSS ne la donne.
     *
     * Le conteneur est rendu inconditionnellement dès que l'épinglage est
     * autorisé, mais il ne porte AUCUN style tant que le script ne l'a pas
     * activé : sans JavaScript, c'est un `<div>` transparent.
     */
    ?>
    <div class="carousel-pin" data-carousel-pin>
<?php endif; ?>

<div class="carousel<?php echo $modifier === '' ? '' : ' carousel--' . esc_attr($modifier); ?>" data-carousel<?php echo $isPinned ? ' data-carousel-pinned' : ''; ?> style="--rail-height: <?php echo esc_attr((string) $height); ?>px">
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

        <?php if ($hasArrows) : ?>
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
        <?php endif; ?>
    </div>
</div>

<?php if ($isPinned) : ?>
    </div>
<?php endif; ?>
