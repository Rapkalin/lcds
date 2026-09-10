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
 *   items array   Une entrée par traitement : title, text (contenu riche),
 *                 open (booléen).
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

<section class="block-treatments"<?php echo $headingId === '' ? '' : ' aria-labelledby="' . esc_attr($headingId) . '"'; ?>>
    <div class="block-treatments__inner">
        <div class="block-treatments__label">
            <?php get_template_part('components/tag', null, ['label' => $label, 'dot' => $dot, 'element' => 'h2', 'id' => $headingId]); ?>
        </div>

        <div class="block-treatments__content">
            <?php get_template_part('components/accordion', null, ['items' => $rows]); ?>

            <div class="block-treatments__cta">
                <?php get_template_part('components/cta', null, $cta); ?>
            </div>
        </div>
    </div>
</section>
