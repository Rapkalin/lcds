<?php

/**
 * Section « informations pratiques » : deux colonnes de 553, séparées de 12.
 *
 * À gauche l'étiquette et un visuel de 440 × 549. À droite une liste d'entrées,
 * chacune composée d'une icône de 24, d'un titre, d'un sur-titre facultatif, du
 * contenu, et d'un bouton d'action facultatif. Les entrées sont séparées par un
 * filet de 1px — mesurés sur le PDF à y=274, 475, 625 et 771 de la bande.
 *
 * Ce n'est PAS une liste de définitions : chaque entrée porte un titre de
 * niveau 3 sous le `h2` de la section, comme les autres blocs, pour que le plan
 * de titres reste navigable — voir readme/accessibilite.md.
 *
 * Arguments (via get_template_part) :
 *   label   string Libellé de l'étiquette.
 *   dot     string Couleur de la puce de l'étiquette.
 *   image   int    Identifiant d'attachement du visuel de gauche.
 *   entries array  Une entrée par bloc d'information :
 *                    icon     string Valeur de LcdsInfoIcon.
 *                    title    string Titre de l'entrée.
 *                    overline string Sur-titre, en capitales espacées.
 *                    text     string Contenu riche.
 *                    cta      array  Arguments d'un bouton contourné.
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

$rows = [];

foreach ($entries as $entry) {
    $title = isset($entry['title']) ? (string) $entry['title'] : '';

    if ($title === '') {
        continue;
    }

    $rows[] = [
        'icon' => LcdsInfoIcon::fromValue($entry['icon'] ?? '', LcdsInfoIcon::Info),
        'title' => $title,
        'overline' => isset($entry['overline']) ? (string) $entry['overline'] : '',
        'text' => isset($entry['text']) ? (string) $entry['text'] : '',
        'cta' => isset($entry['cta']) && is_array($entry['cta']) ? $entry['cta'] : [],
    ];
}

if ($rows === []) {
    return;
}

$visual = $image === 0 ? '' : lcds_render_image($image, ['class' => 'block-info__image'], 'large');
?>

<section class="block-info">
    <div class="block-info__inner">
        <div class="block-info__aside">
            <?php get_template_part('components/tag', null, ['label' => $label, 'dot' => $dot, 'element' => 'h2']); ?>

            <div class="block-info__media">
                <?php if ($visual !== '') : ?>
                    <?php echo $visual; ?>
                <?php endif; ?>
            </div>
        </div>

        <ul class="block-info__list">
            <?php foreach ($rows as $row) : ?>
                <li class="block-info__entry">
                    <span class="block-info__icon">
                        <?php get_template_part($row['icon']->template()); ?>
                    </span>

                    <div class="block-info__head">
                        <h3 class="block-info__title"><?php echo esc_html($row['title']); ?></h3>
                        <?php get_template_part('components/cta', null, $row['cta'] + ['variant' => 'outline']); ?>
                    </div>

                    <div class="block-info__body">
                        <?php if ($row['overline'] !== '') : ?>
                            <p class="block-info__overline"><?php echo esc_html($row['overline']); ?></p>
                        <?php endif; ?>

                        <div class="block-info__text">
                            <?php echo wp_kses_post($row['text']); ?>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
