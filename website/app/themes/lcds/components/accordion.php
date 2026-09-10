<?php

/**
 * Accordéon : une liste de panneaux dépliables, UN SEUL ouvert à la fois.
 *
 * Arguments (via get_template_part) :
 *   items array  Une entrée par panneau :
 *                   id    string Identifiant du panneau, cible de
 *                                `aria-controls`. Doit être unique dans la page.
 *                   title string Libellé du déclencheur.
 *                   text  string Contenu riche du panneau. Assaini par
 *                                wp_kses_post : c'est de la saisie de
 *                                contributeur.
 *                   open  bool   Panneau déplié au chargement.
 *   heading string Niveau de titre portant le déclencheur, `h3` par défaut.
 *                  À ajuster selon la page : un accordéon posé directement sous
 *                  le `h1` prend `h2`, sinon la hiérarchie saute un niveau.
 *
 * L'EXCLUSIVITÉ est portée par `data-disclosure-group` sur la liste, et le
 * script s'en sert pour refermer les voisins. Sans cet attribut, les panneaux
 * seraient indépendants : c'est donc le conteneur qui déclare la règle, pas le
 * script qui la devine. Voir readme/front.md.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

$items = isset($args['items']) && is_array($args['items']) ? $args['items'] : [];

if ($items === []) {
    return;
}

$heading = isset($args['heading']) ? (string) $args['heading'] : 'h3';
$heading = in_array($heading, ['h2', 'h3', 'h4'], true) ? $heading : 'h3';
?>

<ul class="accordion" data-disclosure-group>
    <?php foreach ($items as $item) : ?>
        <?php
        $id = isset($item['id']) ? (string) $item['id'] : '';
        $title = isset($item['title']) ? (string) $item['title'] : '';
        $text = isset($item['text']) ? (string) $item['text'] : '';
        $isOpen = ! empty($item['open']);
        ?>
        <li class="accordion__item">
            <<?php echo $heading; ?> class="accordion__heading">
                <button
                    class="accordion__trigger"
                    type="button"
                    data-disclosure
                    aria-expanded="<?php echo $isOpen ? 'true' : 'false'; ?>"
                    aria-controls="<?php echo esc_attr($id); ?>"
                >
                    <span class="accordion__title"><?php echo esc_html($title); ?></span>
                    <span class="accordion__icon">
                        <?php get_template_part('components/icon-plus'); ?>
                    </span>
                </button>
            </<?php echo $heading; ?>>

            <div class="accordion__panel" id="<?php echo esc_attr($id); ?>" <?php echo $isOpen ? '' : 'hidden'; ?>>
                <?php echo wp_kses_post($text); ?>
            </div>
        </li>
    <?php endforeach; ?>
</ul>
