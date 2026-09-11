<?php

/**
 * Liste d'informations : une icône, un titre, un sur-titre facultatif, du
 * contenu riche, et un bouton d'action facultatif. Les entrées sont séparées
 * par un filet.
 *
 * PARTAGÉE entre la section « informations pratiques » de l'accueil et la
 * section « nous trouver » du cabinet. Les deux maquettes dessinent la même
 * liste, aux mêmes icônes, avec le même contenu — seule la gouttière entre
 * l'icône et le texte change, et elle est en propriété personnalisée.
 *
 * Ce n'est PAS une liste de définitions : chaque entrée porte un titre de
 * niveau 3, pour que le plan de titres reste navigable — voir
 * readme/accessibilite.md.
 *
 * Arguments (via get_template_part) :
 *   entries array Une entrée par bloc d'information :
 *                   icon     string Valeur de LcdsInfoIcon.
 *                   title    string Titre de l'entrée. Une entrée sans titre
 *                                   est ignorée : c'est lui qui la nomme.
 *                   overline string Sur-titre, en capitales espacées.
 *                   text     string Contenu riche. Assaini par wp_kses_post.
 *                   cta      array  Arguments d'un bouton contourné.
 *   heading string Niveau de titre des entrées, `h3` par défaut. À ajuster
 *                  selon la page : une liste posée sous le `h1` prend `h2`.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$entries = isset($args['entries']) && is_array($args['entries']) ? $args['entries'] : [];
$heading = isset($args['heading']) ? (string) $args['heading'] : 'h3';
$heading = in_array($heading, ['h2', 'h3', 'h4'], true) ? $heading : 'h3';

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
?>

<ul class="info-list">
    <?php foreach ($rows as $row) : ?>
        <li class="info-list__entry">
            <span class="info-list__icon">
                <?php get_template_part($row['icon']->template()); ?>
            </span>

            <div class="info-list__head">
                <<?php echo $heading; ?> class="info-list__title"><?php echo esc_html($row['title']); ?></<?php echo $heading; ?>>
                <?php get_template_part('components/cta', null, $row['cta'] + ['variant' => LcdsCtaVariant::Secondary->value]); ?>
            </div>

            <div class="info-list__body">
                <?php if ($row['overline'] !== '') : ?>
                    <p class="info-list__overline"><?php echo esc_html($row['overline']); ?></p>
                <?php endif; ?>

                <div class="info-list__text">
                    <?php echo wp_kses_post($row['text']); ?>
                </div>
            </div>
        </li>
    <?php endforeach; ?>
</ul>
