<?php

/**
 * Hero de la page d'accueil : visuel pleine largeur, carte d'appel en bas à
 * droite.
 *
 * Arguments (via get_template_part) :
 *   image      int|array  Visuel de fond — identifiant d'attachement ou tableau
 *                         ACF. Vide : un aplat tient la place.
 *   video      int        Vidéo de fond. Posée, elle REMPLACE l'image : celle-ci
 *                         devient son image d'attente, et n'est donc pas rendue
 *                         une seconde fois.
 *   thumbnail  int|array  Vignette de la carte, même règle.
 *   label      string     Libellé court de la carte, en capitales.
 *   text       string     Phrase d'accroche de la carte.
 *   url        string     Destination de la carte. Vide : rien n'est cliquable,
 *                         plutôt qu'un lien mort.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$image = $args['image'] ?? 0;
$video = isset($args['video']) ? (int) $args['video'] : 0;
$thumbnail = $args['thumbnail'] ?? 0;
$label = isset($args['label']) ? (string) $args['label'] : '';
$text = isset($args['text']) ? (string) $args['text'] : '';
$url = isset($args['url']) ? (string) $args['url'] : '';

$background = $image === 0 ? '' : lcds_render_image($image, [
    'class' => 'hero__image',
    'loading' => 'eager',
    'fetchpriority' => 'high',
], 'full');

// `wp_get_attachment_url` rend une chaîne vide si l'identifiant ne désigne plus
// rien — un fichier supprimé de la médiathèque sans que le champ soit vidé.
$videoUrl = $video === 0 ? '' : (string) wp_get_attachment_url($video);

// L'image d'attente, et le SEUL rendu sous « animations réduites » ou sans
// JavaScript : c'est le script qui lance la lecture, jamais l'attribut
// `autoplay`. Posé dans le balisage, il aurait fait jouer quelques images avant
// que le script puisse l'arrêter — exactement ce que la préférence interdit.
$poster = $videoUrl === '' || $image === 0
    ? ''
    : (string) wp_get_attachment_image_url($image, 'full');

// `eager` comme le visuel de fond : la carte est posée DANS le hero, donc
// au-dessus de la ligne de flottaison. Le repli du thème est `lazy`, et il
// retarderait une image que le visiteur voit tout de suite.
$vignette = $thumbnail === 0 ? '' : lcds_render_image($thumbnail, [
    'class' => 'hero__thumbnail-image',
    'loading' => 'eager',
], 'medium');

$card_tag = $url === '' ? 'div' : 'a';
?>

<section class="hero">
    <?php if ($videoUrl !== '') : ?>
        <video
            class="hero__image hero__video"
            data-hero-video
            src="<?php echo esc_url($videoUrl); ?>"
            <?php echo $poster === '' ? '' : 'poster="' . esc_url($poster) . '"'; ?>
            preload="metadata"
            muted
            loop
            playsinline
        ></video>

        <?php /* `hidden` tant que le script n'a pas pris la main : sans lui la vidéo ne joue pas, et un bouton qui n'arrête rien n'est pas un bouton. */ ?>
        <button
            class="hero__motion"
            type="button"
            data-hero-motion
            data-label-pause="<?php echo esc_attr__('Mettre en pause la vidéo de fond', 'lcds'); ?>"
            data-label-play="<?php echo esc_attr__('Lancer la vidéo de fond', 'lcds'); ?>"
            hidden
        >
            <?php get_template_part('components/icon-motion'); ?>
            <span class="screen-reader-text" data-hero-motion-label></span>
        </button>
    <?php elseif ($background !== '') : ?>
        <?php echo $background; ?>
    <?php else : ?>
        <div class="hero__image hero__image--placeholder" aria-hidden="true"></div>
    <?php endif; ?>

    <<?php echo $card_tag; ?> class="hero__card"<?php echo $url === '' ? '' : ' href="' . esc_url($url) . '"'; ?>>
        <div class="hero__thumbnail">
            <?php if ($vignette !== '') : ?>
                <?php echo $vignette; ?>
            <?php endif; ?>
        </div>

        <div class="hero__card-body">
            <p class="hero__card-label">
                <?php echo esc_html($label); ?>
                <?php get_template_part('components/icon-calendar'); ?>
            </p>

            <?php if ($text !== '') : ?>
                <p class="hero__card-text"><?php echo esc_html($text); ?></p>
            <?php endif; ?>
        </div>
    </<?php echo $card_tag; ?>>
</section>
