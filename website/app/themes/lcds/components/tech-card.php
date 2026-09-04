<?php

/**
 * Carte du carrousel Technologies : un visuel plein cadre, un titre, et un
 * bouton qui révèle le texte par-dessus la photo.
 *
 * C'est un panneau à révéler, comme l'accordéon des traitements, donc le même
 * balisage : un `<button aria-expanded aria-controls>` et un panneau réellement
 * `hidden`. Sans le `hidden`, le texte resterait dans l'arbre d'accessibilité
 * et tabulable alors qu'il est invisible.
 *
 * La maquette dessine la première carte OUVERTE. Ça se lit comme une
 * démonstration de l'état ouvert, pas comme une règle — d'où le champ.
 *
 * Arguments (via get_template_part) :
 *   id    string Identifiant du panneau, unique dans la page.
 *   title string Titre de la carte.
 *   text  string Contenu riche révélé par le bouton.
 *   image int    Identifiant d'attachement du visuel.
 *   open  bool   Panneau ouvert au chargement.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$id = isset($args['id']) ? (string) $args['id'] : '';
$title = isset($args['title']) ? (string) $args['title'] : '';
$text = isset($args['text']) ? (string) $args['text'] : '';
$image = isset($args['image']) ? (int) $args['image'] : 0;
$is_open = ! empty($args['open']);

if ($id === '' || $title === '') {
    return;
}

$visual = $image === 0 ? '' : lcds_render_image($image, ['class' => 'tech-card__image'], 'large');
?>

<article class="tech-card<?php echo $is_open ? ' tech-card--open' : ''; ?>">
    <?php if ($visual !== '') : ?>
        <?php echo $visual; ?>
    <?php endif; ?>

    <span class="tech-card__scrim" aria-hidden="true"></span>

    <h3 class="tech-card__title"><?php echo esc_html($title); ?></h3>

    <?php if ($text !== '') : ?>
        <div class="tech-card__panel" id="<?php echo esc_attr($id); ?>" <?php echo $is_open ? '' : 'hidden'; ?>>
            <?php echo wp_kses_post($text); ?>
        </div>

        <button
            class="tech-card__trigger"
            type="button"
            data-disclosure
            aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
            aria-controls="<?php echo esc_attr($id); ?>"
        >
            <?php get_template_part('components/icon-plus'); ?>
            <span class="screen-reader-text">
                <?php printf(
                    /* translators: %s : titre de la technologie. */
                    esc_html__('En savoir plus sur %s', 'lcds'),
                    esc_html($title),
                ); ?>
            </span>
        </button>
    <?php endif; ?>
</article>
