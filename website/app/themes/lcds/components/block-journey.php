<?php

/**
 * Section « le parcours de soin » : six étapes parcourues horizontalement au
 * défilement vertical.
 *
 * La section est haute de plusieurs écrans ; à l'intérieur, une vue collée
 * (`sticky`) contient un rail que le JavaScript décale selon l'avancement du
 * défilement. C'est ce même avancement qui remplit la barre de progression, par
 * une seule variable CSS — la barre et le rail ne peuvent donc pas se
 * désynchroniser.
 *
 * Sans JavaScript, ou si l'utilisateur demande à réduire les animations, les
 * étapes s'empilent verticalement : le contenu reste intégralement accessible.
 *
 * Arguments (via get_template_part) :
 *   label string  Libellé de l'étiquette.
 *   dot   string  Couleur de la puce de l'étiquette.
 *   steps array   Une entrée par étape :
 *                   title    string  Titre de l'étape.
 *                   text     string  Contenu riche : paragraphes et listes.
 *                   duration string  Durée indicative, facultative.
 *                   images   array   Cadres : chacun `width` en pixels, et
 *                                    `image` (identifiant d'attachement).
 *
 * Les étapes sont normalisées AVANT le balisage : une instruction PHP au milieu
 * du HTML se fait désaligner par Pint (`statement_indentation`).
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$label = isset($args['label']) ? (string) $args['label'] : '';
$dot = isset($args['dot']) ? (string) $args['dot'] : 'orange';
$steps = isset($args['steps']) && is_array($args['steps']) ? $args['steps'] : [];

$rows = [];

foreach ($steps as $index => $step) {
    $title = isset($step['title']) ? (string) $step['title'] : '';

    if ($title === '') {
        continue;
    }

    $medias = [];

    foreach ((isset($step['images']) && is_array($step['images']) ? $step['images'] : []) as $frame) {
        $attachment = isset($frame['image']) ? (int) $frame['image'] : 0;
        $medias[] = [
            'width' => isset($frame['width']) ? (float) $frame['width'] : 0.0,
            'html' => $attachment === 0 ? '' : lcds_render_image($attachment, [
                'class' => 'journey__image',
            ], 'large'),
        ];
    }

    $rows[] = [
        // Numéroté à partir de 1 et sur deux chiffres, comme sur la maquette.
        'number' => str_pad((string) ((int) $index + 1), 2, '0', STR_PAD_LEFT),
        'title' => $title,
        'text' => isset($step['text']) ? (string) $step['text'] : '',
        'duration' => isset($step['duration']) ? (string) $step['duration'] : '',
        'medias' => $medias,
    ];
}

if ($rows === []) {
    return;
}

$total = count($rows);
?>

<section class="journey" data-journey style="--journey-steps: <?php echo esc_attr((string) $total); ?>">
    <div class="journey__viewport">
        <div class="journey__head">
            <div class="journey__label">
                <?php get_template_part('components/tag', null, ['label' => $label, 'dot' => $dot, 'element' => 'h2']); ?>
            </div>

            <div class="journey__progress">
                <span class="journey__progress-fill" data-journey-fill></span>
            </div>
        </div>

        <ol class="journey__track" data-journey-track>
            <?php foreach ($rows as $row) : ?>
                <li class="journey__step">
                    <div class="journey__step-inner">
                        <p class="journey__number"><?php echo esc_html($row['number']); ?></p>

                        <div class="journey__body">
                            <h3 class="journey__title"><?php echo esc_html($row['title']); ?></h3>

                            <div class="journey__text">
                                <?php echo wp_kses_post($row['text']); ?>
                            </div>

                            <?php if ($row['duration'] !== '') : ?>
                                <p class="journey__duration"><?php echo esc_html($row['duration']); ?></p>
                            <?php endif; ?>

                            <?php if ($row['medias'] !== []) : ?>
                                <div class="journey__medias">
                                    <?php foreach ($row['medias'] as $media) : ?>
                                        <div class="journey__media" style="--media-width: <?php echo esc_attr((string) $media['width']); ?>px">
                                            <?php echo $media['html']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
