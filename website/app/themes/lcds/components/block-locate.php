<?php

/**
 * Section « nous trouver » : un titre, un plan à gauche, la liste
 * d'informations à droite.
 *
 * La liste est le composant `info-list`, le MÊME que celui des informations
 * pratiques de l'accueil — mêmes icônes, mêmes entrées, mêmes filets. Seule sa
 * gouttière change, et elle est posée en CSS.
 *
 * Le plan est une IMAGE, pas une carte interactive : c'est le bouton de la
 * première entrée qui renvoie vers le plan en ligne. Une carte embarquée
 * dépose des cookies et demanderait un consentement préalable — voir
 * readme/contribution.md.
 *
 * Arguments (via get_template_part) :
 *   title   string Titre de la section, qui la nomme dans le plan de la page.
 *   image   int    Identifiant d'attachement du plan.
 *   entries array  Entrées de la liste — voir `info-list`.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$title = isset($args['title']) ? (string) $args['title'] : '';
$image = isset($args['image']) ? (int) $args['image'] : 0;
$entries = isset($args['entries']) && is_array($args['entries']) ? $args['entries'] : [];

// Voir block-intro : une `<section>` sans nom accessible n'est pas exposée
// comme région, et `wp_unique_id` évite la collision si la page en portait deux.
$headingId = $title === '' ? '' : wp_unique_id('section-titre-');

// `eager` et priorisé : le plan est le plus grand visuel du premier écran de
// cette page, posé à 394 du haut. Le repli du thème est `lazy`, et il
// retardait le plus gros élément de contenu affiché.
$visual = $image === 0 ? '' : lcds_render_image($image, [
    'class' => 'block-locate__image',
    'loading' => 'eager',
    'fetchpriority' => 'high',
], 'large');
?>

<section class="block-locate"<?php echo $headingId === '' ? '' : ' aria-labelledby="' . esc_attr($headingId) . '"'; ?>>
    <?php if ($title !== '') : ?>
        <h2 class="block-locate__title" id="<?php echo esc_attr($headingId); ?>"><?php echo esc_html($title); ?></h2>
    <?php endif; ?>

    <div class="block-locate__inner">
        <div class="block-locate__media">
            <?php if ($visual !== '') : ?>
                <?php echo $visual; ?>
            <?php endif; ?>
        </div>

        <?php get_template_part('components/info-list', null, ['entries' => $entries]); ?>
    </div>
</section>
