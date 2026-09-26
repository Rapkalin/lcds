<?php

/**
 * Section « à propos » : un titre, son visuel juste dessous, sur toute la
 * largeur du contenu.
 *
 * Le visuel est un CADRE de rapport 2:1 — 1344 × 672 relevé sur
 * `EQUIPE/LCDS_equipe.pdf` — que l'image remplit en `cover`. Une photo d'un
 * autre rapport est donc recadrée au centre, jamais déformée. La maquette y
 * pose une photo de groupe déjà recadrée : voir `_sources/images/EQUIPE`.
 *
 * Arguments (via get_template_part) :
 *   title   string Titre de la section.
 *   element string `h1` quand ce titre est celui de la page — c'est le cas sur
 *                  « L'équipe », dont la maquette ne dessine aucun autre titre
 *                  de page — ou `h2` quand la section n'en nomme qu'une. `h2`
 *                  par défaut : un gabarit qui ne dit rien ne réclame pas le
 *                  seul `h1` de la page.
 *   image   int    Identifiant d'attachement du visuel.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$title = isset($args['title']) ? (string) $args['title'] : '';
$image = isset($args['image']) ? (int) $args['image'] : 0;

// Allow-list : la balise finit dans le balisage, elle ne peut pas venir
// librement de l'appelant. Même garde-fou que `tag`.
$element = isset($args['element']) && $args['element'] === 'h1' ? 'h1' : 'h2';

if ($title === '' && $image === 0) {
    return;
}

// Voir block-intro : une `<section>` sans nom accessible n'est pas exposée
// comme région, et `wp_unique_id` évite la collision si la page en portait deux.
$headingId = $title === '' ? '' : wp_unique_id('section-titre-');

// `eager` et priorisé : ce visuel est le plus grand du premier écran de la
// page. Le repli du thème est `lazy`, et il retardait le plus gros élément de
// contenu affiché — même raisonnement que le plan de « nous trouver ».
$visual = $image === 0 ? '' : lcds_render_image($image, [
    'class' => 'block-about__image',
    'loading' => 'eager',
    'fetchpriority' => 'high',
], 'full');
?>

<section class="block-about"<?php echo $headingId === '' ? '' : ' aria-labelledby="' . esc_attr($headingId) . '"'; ?>>
    <div class="block-about__inner">
        <?php if ($title !== '') : ?>
            <<?php echo $element; ?> class="block-about__title" id="<?php echo esc_attr($headingId); ?>"><?php echo esc_html($title); ?></<?php echo $element; ?>>
        <?php endif; ?>

        <div class="block-about__media">
            <?php if ($visual !== '') : ?>
                <?php echo $visual; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
