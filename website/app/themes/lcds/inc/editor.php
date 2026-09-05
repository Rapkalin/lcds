<?php

/**
 * Configuration de l'éditeur.
 *
 * La page d'accueil se contribue par un champ de contenu flexible, dans un
 * unique formulaire sous l'éditeur : l'éditeur de blocs y est donc coupé, et
 * son contenu masqué par le `hide_on_screen` du groupe de champs. Deux surfaces
 * de saisie concurrentes sur la même page, c'est la garantie qu'un contributeur
 * remplira la mauvaise.
 *
 * Coupé SECTION PAR SECTION du site, et non globalement : tout ce qui est du
 * texte libre — mentions légales, un futur article — s'écrit mieux en blocs
 * qu'en contenu flexible. Élargir ce filtre à `return false` demande donc une
 * décision, pas un oubli.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Coupe l'éditeur de blocs sur les contenus qui se contribuent par ACF.
 *
 * @param bool    $use_block_editor Décision courante.
 * @param WP_Post $post             Contenu édité.
 */
function lcds_disable_block_editor(bool $use_block_editor, WP_Post $post): bool
{
    return in_array((int) $post->ID, lcds_acf_contributed_posts(), true)
        ? false
        : $use_block_editor;
}
add_filter('use_block_editor_for_post', 'lcds_disable_block_editor', 10, 2);

/**
 * Identifiants des contenus contribués par ACF plutôt que par des blocs.
 *
 * Une liste et non un test sur le gabarit : `front-page.php` n'est pas un
 * gabarit sélectionnable, la page d'accueil se reconnaît au réglage
 * « Lecture ». Ajouter une page à contenu flexible = ajouter son identifiant
 * ici, exactement comme la localisation de son groupe de champs.
 *
 * @return array<int, int>
 */
function lcds_acf_contributed_posts(): array
{
    $front = (int) get_option('page_on_front');

    return $front > 0 ? [$front] : [];
}
