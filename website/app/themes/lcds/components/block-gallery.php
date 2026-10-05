<?php

/**
 * Section « galerie » : le rail de visuels, seul.
 *
 * C'est le carrousel de la section « l'histoire » de l'accueil sans son texte —
 * même composant et mêmes réglages : le rail avance avec le défilement de la
 * page, et n'a donc pas de flèches. Leur absence emporte le glisser-déposer à
 * la souris, dont elles sont l'alternative — voir components/carousel.php.
 *
 * UNE `<section>`, ET C'EST LA MISE EN FORME QUI L'EXIGE : le rythme vertical
 * de la page « Conseils » se règle en `:first-of-type` / `:last-of-type`, qui
 * comptent les `<section>`. Un `<div>` ne serait pas compté, et la section
 * voisine se croirait première ou dernière — voir block-advice.scss.
 *
 * Le libellé du rail nomme la région, faute de titre visible : sans nom, une
 * `<section>` n'est pas exposée comme région, et la navigation par régions
 * s'arrêterait aux quatre repères de la page. Vide, l'attribut n'est pas rendu
 * du tout — `aria-label=""` est traité comme absent, autant ne pas mentir.
 *
 * Arguments (via get_template_part) :
 *   label string Libellé accessible du rail, qui nomme aussi la section.
 *   items array  Éléments du rail — voir components/carousel.php.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$items = isset($args['items']) && is_array($args['items']) ? $args['items'] : [];

if ($items === []) {
    return;
}

$label = isset($args['label']) ? trim((string) $args['label']) : '';
?>

<section class="block-gallery"<?php echo $label === '' ? '' : ' aria-label="' . esc_attr($label) . '"'; ?>>
    <?php get_template_part('components/carousel', null, [
        'items' => $items,
        'label' => $label,
        'pinned' => true,
        'arrows' => false,
    ]); ?>
</section>
