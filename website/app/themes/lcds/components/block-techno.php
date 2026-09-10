<?php

/**
 * Section « les technologies » : étiquette et bouton d'action sur une ligne,
 * puis un rail de cartes inclinées.
 *
 * Le rail et ses contrôles sont CEUX de la galerie d'intro : la maquette les
 * dessine strictement identiques — piste de 214, deux boutons de 52 alignés à
 * droite sur 1279. Le composant `carousel` accepte donc du contenu déjà produit
 * plutôt que de simples visuels.
 *
 * L'ondulation de la maquette n'est PAS un décalage vertical : mesuré sur le
 * PDF, les trois cartes partagent le même centre vertical et seule leur
 * rotation change (+2,88 / 0 / -2,88 degrés). Le relevé `get_metadata` de Figma
 * annonçait « 0 / 11,4 / 23,4 » : c'étaient les boîtes englobantes de cadres
 * pivotés.
 *
 * Arguments (via get_template_part) :
 *   label string Libellé de l'étiquette.
 *   dot   string Couleur de la puce de l'étiquette.
 *   cta   array  Arguments du bouton d'action.
 *   cards array  Une entrée par carte : title, text, image, open.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$label = isset($args['label']) ? (string) $args['label'] : '';
$dot = isset($args['dot']) ? (string) $args['dot'] : 'orange';
$cta = isset($args['cta']) && is_array($args['cta']) ? $args['cta'] : [];
$cards = isset($args['cards']) && is_array($args['cards']) ? $args['cards'] : [];

// Largeurs et inclinaisons alternées, relevées au pixel sur le PDF. Le cycle
// est de trois : la maquette n'en montre que trois cartes, le rail en porte
// neuf.
$widths = [471.5, 447.5, 471.5];
$tilts = [2.88, 0.0, -2.88];
$items = [];

foreach ($cards as $index => $card) {
    $title = isset($card['title']) ? (string) $card['title'] : '';

    if ($title === '') {
        continue;
    }

    $slot = count($items) % 3;
    $items[] = [
        'width' => $widths[$slot],
        'tilt' => $tilts[$slot],
        'content' => lcds_capture('components/tech-card', [
            'id' => 'techno-' . ((int) $index + 1),
            'title' => $title,
            'text' => isset($card['text']) ? (string) $card['text'] : '',
            'image' => isset($card['image']) ? (int) $card['image'] : 0,
            'open' => ! empty($card['open']),
        ]),
    ];
}

if ($items === []) {
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

<?php /* Le groupe d'exclusivité des cartes : une seule dépliée à la fois, */ ?>
<?php /* et refermer une carte ne touche pas l'accordéon des traitements. */ ?>
<section class="block-techno" data-disclosure-group<?php echo $headingId === '' ? '' : ' aria-labelledby="' . esc_attr($headingId) . '"'; ?>>
    <div class="block-techno__header">
        <?php get_template_part('components/tag', null, ['label' => $label, 'dot' => $dot, 'element' => 'h2', 'id' => $headingId]); ?>
        <?php get_template_part('components/cta', null, $cta); ?>
    </div>

    <?php get_template_part('components/carousel', null, [
        'items' => $items,
        'label' => $label,
        'height' => 494,
        'modifier' => 'cards',
    ]); ?>
</section>
