<?php

/**
 * Section « rencontrez l'équipe » : un titre, puis des groupes de personnes.
 *
 * Un groupe = une TÊTE (son étiquette et son texte) suivie de ses cartes, le
 * tout dans une grille de trois colonnes où la tête occupe la première case.
 * C'est ce que dessine la maquette : les cartes de la deuxième rangée
 * reprennent la colonne de gauche, sous la tête.
 *
 * La tête RESTE AU MÊME NIVEAU pendant que sa rangée défile, comme les titres
 * de groupe du cabinet, et la carte qui arrive sous elle la CHASSE. Demande
 * client.
 *
 * D'où l'enveloppe `__cell` : c'est elle qui est la case de grille, et la tête
 * se colle DEDANS. Collée directement dans la grille, sa course n'était pas
 * bornée par sa rangée — mesuré, elle tenait 530px là où sa rangée n'en fait
 * que 377 — et la carte de la rangée suivante lui passait dessus au lieu de la
 * chasser.
 *
 * Chaque groupe porte sa propre grille, et non une grille unique pour la
 * section : la maquette ouvre CHAQUE groupe sur une nouvelle rangée, étiquette
 * à gauche. Une grille commune aurait posé la deuxième étiquette dans la
 * colonne où la précédente s'est arrêtée.
 *
 * Arguments (via get_template_part) :
 *   title   string Titre de la section, qui la nomme dans le plan de la page.
 *   groups  array  Un groupe par entrée :
 *                    label     string Étiquette, rendue en pastille `h3`.
 *                    dot       string Couleur de la puce.
 *                    text      string Texte de présentation du groupe.
 *                    people    array  Voir `person-card`.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$title = isset($args['title']) ? trim((string) $args['title']) : '';
$groups = isset($args['groups']) && is_array($args['groups']) ? $args['groups'] : [];

if ($title === '' && $groups === []) {
    return;
}

// Voir block-intro : une `<section>` sans nom accessible n'est pas exposée
// comme région, et `wp_unique_id` évite la collision si la page en portait deux.
$headingId = $title === '' ? '' : wp_unique_id('section-titre-');
?>

<section class="block-team"<?php echo $headingId === '' ? '' : ' aria-labelledby="' . esc_attr($headingId) . '"'; ?>>
    <?php if ($title !== '') : ?>
        <h2 class="block-team__title" id="<?php echo esc_attr($headingId); ?>"><?php echo esc_html($title); ?></h2>
    <?php endif; ?>

    <?php foreach ($groups as $group) : ?>
        <?php
        $people = isset($group['people']) && is_array($group['people']) ? $group['people'] : [];
        $label = isset($group['label']) ? trim((string) $group['label']) : '';
        $text = isset($group['text']) ? (string) $group['text'] : '';

        if ($label === '' && $text === '' && $people === []) {
            continue;
        }
        ?>

        <div class="block-team__group">
            <div class="block-team__cell">
                <div class="block-team__head">
                    <?php get_template_part('components/tag', null, [
                        'label' => $label,
                        // `h3` et non `h2` : l'étiquette nomme un groupe DANS
                        // la section, dont le titre porte déjà le `h2`. Un `h2`
                        // ici sortirait les groupes de leur section du plan.
                        'element' => 'h3',
                        'dot' => isset($group['dot']) ? (string) $group['dot'] : 'turquoise',
                    ]); ?>

                    <?php if ($text !== '') : ?>
                        <div class="block-team__text"><?php echo wp_kses_post($text); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php foreach ($people as $person) : ?>
                <?php get_template_part('components/person-card', null, [
                    'image' => isset($person['image']) ? (int) $person['image'] : 0,
                    'hover' => isset($person['hover']) ? (int) $person['hover'] : 0,
                    'name' => isset($person['name']) ? (string) $person['name'] : '',
                    'role' => isset($person['role']) ? (string) $person['role'] : '',
                    'bio' => isset($person['bio']) ? (string) $person['bio'] : '',
                ]); ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</section>
