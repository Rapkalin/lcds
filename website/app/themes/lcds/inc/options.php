<?php

/**
 * Réglages du site.
 *
 * Le pied de page est commun à toutes les pages : son contenu n'appartient à
 * aucune d'elles. Il vit donc dans une page d'options ACF, dont la structure
 * est versionnée en JSON local comme le reste — voir readme/contribution.md.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Déclare l'écran « Réglages → Configuration ».
 *
 * Gardée : la page d'options est une fonctionnalité d'ACF Pro, un plugin sous
 * licence hors du dépôt. Le thème doit se dégrader proprement sans lui.
 */
function lcds_register_options_page(): void
{
    if (! function_exists('acf_add_options_sub_page')) {
        return;
    }

    // Sous-page de « Réglages » et non entrée de premier niveau : c'est là
    // qu'un contributeur cherche la configuration du site, et le menu
    // d'administration n'a pas besoin d'une ligne de plus.
    acf_add_options_sub_page([
        'page_title' => __('Configuration du site', 'lcds'),
        'menu_title' => __('Configuration', 'lcds'),
        'menu_slug' => 'lcds-settings',
        'parent_slug' => 'options-general.php',
        'capability' => 'edit_theme_options',
        'redirect' => false,
        'update_button' => __('Enregistrer', 'lcds'),
        'updated_message' => __('Configuration enregistrée.', 'lcds'),
    ]);
}
add_action('acf/init', 'lcds_register_options_page');

/**
 * Valeur d'un champ de la page de réglages.
 *
 * `lcds_field()` interroge le contenu courant : sur le pied de page, ce serait
 * la page affichée et non les réglages. D'où ce raccourci, qui évite d'écrire
 * `'option'` dans chaque gabarit.
 *
 * @param string $selector Nom ou clé du champ.
 */
function lcds_option(string $selector): mixed
{
    return lcds_field($selector, 'option');
}

/**
 * Valeur d'un champ de réglage, ramenée à une chaîne propre.
 *
 * @param string $selector Nom ou clé du champ.
 */
function lcds_option_text(string $selector): string
{
    $value = lcds_option($selector);

    return is_scalar($value) ? trim((string) $value) : '';
}
