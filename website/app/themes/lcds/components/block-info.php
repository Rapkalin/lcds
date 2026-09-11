<?php

/**
 * Section « informations pratiques » : deux colonnes de 553, séparées de 12.
 *
 * À gauche l'étiquette et un visuel de 440 × 549. À droite la liste d'entrées,
 * rendue par `components/info-list` — la même que celle de « nous trouver » sur
 * la page du cabinet. Les filets sont mesurés sur le PDF à y=274, 475, 625 et
 * 771 de la bande.
 *
 * Arguments (via get_template_part) :
 *   label   string Libellé de l'étiquette.
 *   dot     string Couleur de la puce de l'étiquette.
 *   image   int    Identifiant d'attachement du visuel de gauche.
 *   entries array  Une entrée par bloc d'information — voir `info-list`, qui
 *                  en décrit les clés et les normalise.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$label = isset($args['label']) ? (string) $args['label'] : '';
$dot = isset($args['dot']) ? (string) $args['dot'] : 'orange';
$image = isset($args['image']) ? (int) $args['image'] : 0;
$entries = isset($args['entries']) && is_array($args['entries']) ? $args['entries'] : [];

// La liste vide ne suffit pas à taire la section : l'étiquette et le visuel
// valent d'être rendus seuls. C'est `info-list` qui décide de ne rien rendre.
$visual = $image === 0 ? '' : lcds_render_image($image, ['class' => 'block-info__image'], 'large');

// Une `<section>` sans nom accessible n'est PAS exposée comme région : la
// navigation par régions s'arrêterait aux quatre repères de la page. Son
// titre visible la nomme, d'où l'identifiant posé sur l'étiquette.
//
// `wp_unique_id` et non un identifiant écrit en dur : les sections sont un
// contenu flexible, deux du même type peuvent coexister sur une page, et deux
// `id` identiques feraient pointer les deux `aria-labelledby` au même endroit.
//
// Vide quand le libellé l'est : `tag` ne rend alors rien, et un
// `aria-labelledby` qui ne désigne aucun élément ne nomme pas la section.
$headingId = $label === '' ? '' : wp_unique_id('section-titre-');
?>

<section class="block-info"<?php echo $headingId === '' ? '' : ' aria-labelledby="' . esc_attr($headingId) . '"'; ?>>
    <div class="block-info__inner">
        <div class="block-info__aside">
            <?php get_template_part('components/tag', null, ['label' => $label, 'dot' => $dot, 'element' => 'h2', 'id' => $headingId]); ?>

            <div class="block-info__media">
                <?php if ($visual !== '') : ?>
                    <?php echo $visual; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php get_template_part('components/info-list', null, ['entries' => $entries]); ?>
    </div>
</section>
