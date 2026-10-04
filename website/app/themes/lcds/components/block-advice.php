<?php

/**
 * Section de conseils : un titre COLLANT, puis des groupes de deux colonnes —
 * étiquette à gauche, contenu à droite.
 *
 * Deux collages imbriqués, et c'est tout le dispositif :
 *
 *   le TITRE est borné par la section     → seul le titre suivant le chasse ;
 *   l'ÉTIQUETTE est bornée par son groupe → la suivante la chasse en fin de
 *                                           groupe, le titre, lui, reste.
 *
 * L'ENVELOPPE `__cell` n'est pas décorative, c'est elle qui BORNE le collage de
 * l'étiquette. Collée directement dans la grille, l'étiquette prendrait la
 * grille entière pour cage et traverserait tous les groupes au lieu d'être
 * chassée par la suivante. Même piège que `block-team` — voir readme/front.md.
 *
 * Arguments (via get_template_part) :
 *   title  string Titre de la section, rendu collant.
 *   kind   string Valeur d'un cas de `LcdsAdviceSection`. Décide de ce que
 *                 porte la colonne de droite, et du filet entre groupes.
 *   intro  string Chapô, posé dans la colonne de droite au-dessus du premier
 *                 groupe. Contenu riche, assaini par wp_kses_post.
 *   groups array  Une entrée par groupe :
 *                    label   string Libellé de l'étiquette. Vide : pas de
 *                                   pastille, la colonne reste en place.
 *                    dot     string Couleur de la puce.
 *                    heading string Titre du bloc (description).
 *                    text    string Contenu riche (description).
 *                    cta     array  Arguments du bouton d'action (description).
 *                    items   array  Entrées de l'accordéon (accordéon).
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$title = isset($args['title']) ? (string) $args['title'] : '';
$kind = LcdsAdviceSection::fromLayout($args['kind'] ?? null);
$intro = isset($args['intro']) ? (string) $args['intro'] : '';
$groups = isset($args['groups']) && is_array($args['groups']) ? $args['groups'] : [];

if ($kind === null || ($groups === [] && $intro === '')) {
    return;
}

// Une `<section>` sans nom accessible n'est PAS exposée comme région : la
// navigation par régions s'arrêterait aux quatre repères de la page. Son titre
// visible la nomme, d'où l'identifiant posé dessus.
//
// `wp_unique_id` et non un identifiant écrit en dur : la page porte plusieurs
// sections du même type, et deux `id` identiques feraient pointer les deux
// `aria-labelledby` au même endroit.
$headingId = $title === '' ? '' : wp_unique_id('section-titre-');
?>

<section class="block-advice block-advice--<?php echo esc_attr($kind->value); ?>"<?php echo $headingId === '' ? '' : ' aria-labelledby="' . esc_attr($headingId) . '"'; ?>>
    <?php if ($title !== '') : ?>
        <div class="block-advice__head">
            <h2 class="block-advice__title" id="<?php echo esc_attr($headingId); ?>"><?php echo esc_html($title); ?></h2>
        </div>
    <?php endif; ?>

    <div class="block-advice__body">
        <?php if ($intro !== '') : ?>
            <div class="block-advice__group block-advice__group--intro">
                <div class="block-advice__cell"></div>

                <div class="block-advice__content">
                    <div class="block-advice__text"><?php echo wp_kses_post($intro); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php foreach ($groups as $group) : ?>
            <?php
            $label = isset($group['label']) ? (string) $group['label'] : '';
            $dot = isset($group['dot']) ? (string) $group['dot'] : 'orange';
            $heading = isset($group['heading']) ? (string) $group['heading'] : '';
            $text = isset($group['text']) ? (string) $group['text'] : '';
            $cta = isset($group['cta']) && is_array($group['cta']) ? $group['cta'] : [];
            $items = isset($group['items']) && is_array($group['items']) ? $group['items'] : [];

            // Le bouton est testé sur le MÊME critère que `cta` lui-même : sans
            // libellé ou sans destination il ne rend rien, et son enveloppe
            // vide garderait ses 80px de réserve au bas du bloc — mesuré sur
            // les trois blocs de « coûts et prise en charge », qui n'en ont
            // aucun.
            $hasCta = trim((string) ($cta['label'] ?? '')) !== ''
                && trim((string) ($cta['url'] ?? '')) !== '';

            // Le niveau des titres de la colonne de droite dépend de la
            // présence de l'étiquette, et il ne peut pas en être autrement :
            // elle porte le `h3` qui range le groupe, et sans elle un `h4`
            // sauterait un niveau sous le `h2` de la section. C'est le cas de
            // la première section de la maquette, qui n'a aucune étiquette.
            $level = $label === '' ? 'h3' : 'h4';
            ?>
            <div class="block-advice__group">
                <div class="block-advice__cell">
                    <div class="block-advice__label">
                        <?php get_template_part('components/tag', null, ['label' => $label, 'dot' => $dot, 'element' => 'h3']); ?>
                    </div>
                </div>

                <div class="block-advice__content">
                    <?php if ($kind === LcdsAdviceSection::Accordion) : ?>
                        <?php get_template_part('components/accordion', null, [
                            'items' => $items,
                            'heading' => $level,
                            'variant' => 'flush',
                        ]); ?>
                    <?php else : ?>
                        <?php if ($heading !== '') : ?>
                            <<?php echo $level; ?> class="block-advice__heading"><?php echo esc_html($heading); ?></<?php echo $level; ?>>
                        <?php endif; ?>

                        <?php if ($text !== '') : ?>
                            <div class="block-advice__text"><?php echo wp_kses_post($text); ?></div>
                        <?php endif; ?>

                        <?php if ($hasCta) : ?>
                            <div class="block-advice__cta">
                                <?php get_template_part('components/cta', null, $cta); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
