<?php

/**
 * Section « nous trouver » : une tête à gauche — son libellé puis le plan —,
 * la liste d'informations à droite.
 *
 * La TÊTE EST D'UN SEUL TENANT, et c'est elle qui se colle. Séparer le libellé
 * du plan revenait à coller deux boîtes l'une sous l'autre, donc à écrire
 * quelque part la hauteur de la première : elle se serait recouverte dès que le
 * libellé passe à la ligne. Ici le plan suit son libellé dans le même bloc.
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
 *   title   string Libellé de la section, qui la nomme dans le plan de la page.
 *   mode    string `tag` — le libellé est une pastille, ce que dessine la
 *                  maquette — ou `title`, où il est rendu comme les titres des
 *                  sections suivantes. Dans les deux cas c'est un `h2` : seule
 *                  sa forme change, jamais sa place dans le plan de titres.
 *   image   int    Identifiant d'attachement du plan.
 *   entries array  Entrées de la liste — voir `info-list`.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$title = isset($args['title']) ? (string) $args['title'] : '';
$mode = isset($args['mode']) && $args['mode'] === 'title' ? 'title' : 'tag';
$image = isset($args['image']) ? (int) $args['image'] : 0;
$entries = isset($args['entries']) && is_array($args['entries']) ? $args['entries'] : [];

// Voir block-intro : une `<section>` sans nom accessible n'est pas exposée
// comme région, et `wp_unique_id` évite la collision si la page en portait deux.
$headingId = $title === '' ? '' : wp_unique_id('section-titre-');

// `eager` et priorisé : le plan est le plus grand visuel du premier écran de
// cette page, posé à 519 du haut. Le repli du thème est `lazy`, et il
// retardait le plus gros élément de contenu affiché.
$visual = $image === 0 ? '' : lcds_render_image($image, [
    'class' => 'block-locate__image',
    'loading' => 'eager',
    'fetchpriority' => 'high',
], 'large');
?>

<section class="block-locate"<?php echo $headingId === '' ? '' : ' aria-labelledby="' . esc_attr($headingId) . '"'; ?>>
    <div class="block-locate__inner">
        <div class="block-locate__head">
            <?php if ($title !== '' && $mode === 'tag') : ?>
                <?php get_template_part('components/tag', null, [
                    'label' => $title,
                    'element' => 'h2',
                    'id' => $headingId,
                    'dot' => LcdsDotColor::Orange->value,
                ]); ?>
            <?php elseif ($title !== '') : ?>
                <h2 class="block-locate__title" id="<?php echo esc_attr($headingId); ?>"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>

            <div class="block-locate__media">
                <?php if ($visual !== '') : ?>
                    <?php echo $visual; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php get_template_part('components/info-list', null, ['entries' => $entries]); ?>
    </div>
</section>
