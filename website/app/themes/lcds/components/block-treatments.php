<?php

/**
 * Section « les différents traitements » : étiquette à gauche, accordéon à
 * droite, bouton d'action en pied de colonne.
 *
 * Chaque entrée suit le motif de divulgation recommandé — un `button` porteur de
 * `aria-expanded` à l'intérieur du titre, et un panneau `hidden` qu'il commande.
 * Sans JavaScript les panneaux restent fermés mais le balisage reste valide.
 *
 * Arguments (via get_template_part) :
 *   label string  Libellé de l'étiquette.
 *   dot   string  Couleur de la puce de l'étiquette.
 *   items array   Une entrée par traitement : title, text, open (booléen).
 *   cta   array   Arguments du bouton d'action.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$label = isset($args['label']) ? (string) $args['label'] : '';
$dot = isset($args['dot']) ? (string) $args['dot'] : 'turquoise';
$items = isset($args['items']) && is_array($args['items']) ? $args['items'] : [];
$cta = isset($args['cta']) && is_array($args['cta']) ? $args['cta'] : [];

$rows = [];

foreach ($items as $index => $item) {
    $title = isset($item['title']) ? (string) $item['title'] : '';

    if ($title === '') {
        continue;
    }

    $rows[] = [
        'title' => $title,
        'text' => isset($item['text']) ? (string) $item['text'] : '',
        'open' => ! empty($item['open']),
        'id' => 'treatment-' . ((int) $index + 1),
    ];
}

if ($rows === []) {
    return;
}
?>

<section class="block-treatments">
    <div class="block-treatments__inner">
        <div class="block-treatments__label">
            <?php get_template_part('components/tag', null, ['label' => $label, 'dot' => $dot]); ?>
        </div>

        <div class="block-treatments__content">
            <ul class="accordion">
                <?php foreach ($rows as $row) : ?>
                    <li class="accordion__item">
                        <h2 class="accordion__heading">
                            <button
                                class="accordion__trigger"
                                type="button"
                                aria-expanded="<?php echo $row['open'] ? 'true' : 'false'; ?>"
                                aria-controls="<?php echo esc_attr($row['id']); ?>"
                            >
                                <span class="accordion__title"><?php echo esc_html($row['title']); ?></span>
                                <span class="accordion__icon">
                                    <?php get_template_part('components/icon-plus'); ?>
                                </span>
                            </button>
                        </h2>

                        <div class="accordion__panel" id="<?php echo esc_attr($row['id']); ?>" <?php echo $row['open'] ? '' : 'hidden'; ?>>
                            <?php if ($row['text'] !== '') : ?>
                                <p><?php echo esc_html($row['text']); ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="block-treatments__cta">
                <?php get_template_part('components/cta', null, $cta); ?>
            </div>
        </div>
    </div>
</section>
