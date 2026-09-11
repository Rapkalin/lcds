<?php

/**
 * Section « l'app mobile » : bande pleine largeur, visuel de fond, colonne de
 * gauche avec l'étiquette, le titre et le QR code.
 *
 * Arguments (via get_template_part) :
 *   label string Libellé de l'étiquette, et TITRE de la section.
 *   dot   string Couleur de la puce de l'étiquette.
 *   title string Titre affiché en grand. Rendu en `h3` : voir plus bas.
 *   image int    Visuel de fond, sur toute la largeur de l'écran.
 *   code  int    QR code menant à l'application.
 *   url   string Destination du QR code. Le code devient alors un lien.
 *
 * Le titre est un `h3` sous le `h2` de l'étiquette, exactement comme les
 * entrées d'accordéon et les étapes du parcours : le NIVEAU dit la hiérarchie,
 * la CLASSE dit l'apparence, et la classe lui rend la taille d'un `h2`. Le
 * passer en `h2` sortirait l'étiquette du plan de la page — voir
 * readme/accessibilite.md, c'est la règle que ce thème s'est donnée.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$label = isset($args['label']) ? (string) $args['label'] : '';
$dot = isset($args['dot']) ? (string) $args['dot'] : 'orange';
$title = isset($args['title']) ? (string) $args['title'] : '';
$image = isset($args['image']) ? (int) $args['image'] : 0;
$code = isset($args['code']) ? (int) $args['code'] : 0;
$url = isset($args['url']) ? (string) $args['url'] : '';

// Voir block-intro : une `<section>` sans nom accessible n'est pas exposée
// comme région, et `wp_unique_id` évite la collision si deux sections du même
// type coexistent sur une page.
$headingId = $label === '' ? '' : wp_unique_id('section-titre-');

// Rien à montrer : ni visuel, ni titre. La bande ne se rend pas du tout plutôt
// que de poser un aplat vide sur toutes les pages du site.
if ($image === 0 && $title === '' && $label === '') {
    return;
}

$visual = $image === 0 ? '' : lcds_render_image($image, [
    'class' => 'block-app__image',
], 'full');

$badge = $code === 0 ? '' : lcds_render_image($code, [
    'class' => 'block-app__code-image',
], 'medium');
?>

<section class="block-app"<?php echo $headingId === '' ? '' : ' aria-labelledby="' . esc_attr($headingId) . '"'; ?>>
    <?php if ($visual !== '') : ?>
        <?php echo $visual; ?>
    <?php else : ?>
        <div class="block-app__image block-app__image--placeholder" aria-hidden="true"></div>
    <?php endif; ?>

    <div class="block-app__inner">
        <?php get_template_part('components/tag', null, [
            'label' => $label,
            'dot' => $dot,
            'element' => 'h2',
            'id' => $headingId,
        ]); ?>

        <?php if ($title !== '') : ?>
            <h3 class="block-app__title"><?php echo esc_html($title); ?></h3>
        <?php endif; ?>

        <?php if ($badge !== '') : ?>
            <?php if ($url === '') : ?>
                <div class="block-app__code"><?php echo $badge; ?></div>
            <?php else : ?>
                <a class="block-app__code" href="<?php echo esc_url($url); ?>"><?php echo $badge; ?></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
