<?php

/**
 * Carte d'une personne de l'équipe : son portrait, puis son nom et sa fonction
 * sur un bandeau blanc.
 *
 * LE PORTRAIT EST CADRÉ PAR LE HAUT, et non au centre : les photos sont des
 * portraits en pied de buste, deux fois plus hautes que larges, et le cadre est
 * CARRÉ. Un cadrage centré coupait le haut du crâne — vérifié sur la maquette,
 * qui montre bien le carré supérieur de la photo.
 *
 * Arguments (via get_template_part) :
 *   image int    Identifiant d'attachement du portrait. Absent : le cadre reste
 *                dessiné, comme sur la maquette où dix-sept cartes sur
 *                vingt-neuf attendent encore leur photo.
 *   name  string Nom de la personne.
 *   role  string Fonction, rendue en capitales par le CSS.
 *   bio   string Texte révélé par le bouton. Vide : PAS DE BOUTON — un bouton
 *                qui n'ouvre rien n'est pas un bouton.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$image = isset($args['image']) ? (int) $args['image'] : 0;
$name = isset($args['name']) ? trim((string) $args['name']) : '';
$role = isset($args['role']) ? trim((string) $args['role']) : '';
$bio = isset($args['bio']) ? (string) $args['bio'] : '';

if ($image === 0 && $name === '' && $role === '') {
    return;
}

$panelId = $bio === '' ? '' : wp_unique_id('personne-bio-');

$visual = $image === 0 ? '' : lcds_render_image($image, ['class' => 'person-card__image'], 'medium_large');
?>

<article class="person-card">
    <div class="person-card__media">
        <?php if ($visual !== '') : ?>
            <?php echo $visual; ?>
        <?php endif; ?>
    </div>

    <div class="person-card__foot">
        <div class="person-card__identity">
            <?php if ($name !== '') : ?>
                <h4 class="person-card__name"><?php echo esc_html($name); ?></h4>
            <?php endif; ?>

            <?php if ($role !== '') : ?>
                <p class="person-card__role"><?php echo esc_html($role); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($bio !== '') : ?>
            <button
                class="person-card__toggle"
                type="button"
                data-disclosure
                aria-expanded="false"
                aria-controls="<?php echo esc_attr($panelId); ?>"
            >
                <?php get_template_part('components/icon-plus'); ?>
                <span class="screen-reader-text">
                    <?php printf(
                        /* translators: %s : nom de la personne. */
                        esc_html__('En savoir plus sur %s', 'lcds'),
                        esc_html($name),
                    ); ?>
                </span>
            </button>
        <?php endif; ?>
    </div>

    <?php if ($bio !== '') : ?>
        <div class="person-card__bio" id="<?php echo esc_attr($panelId); ?>" hidden>
            <?php echo wp_kses_post($bio); ?>
        </div>
    <?php endif; ?>
</article>
