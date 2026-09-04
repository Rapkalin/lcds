<?php

/**
 * Enregistrement des blocs de l'éditeur.
 *
 * Chaque section du site est un bloc, décrit par son propre `block.json` sous
 * `blocks/`. Les blocs portant une clé `acf` sont rendus par ACF via leur
 * `renderTemplate` ; WordPress ne lit ici que leurs métadonnées.
 *
 * Convention de nommage : `acf/lcds-<section>`, titre « LCDS — … », catégorie
 * `lcds`. Le préfixe est explicite dans le nom du bloc pour qu'aucun bloc du
 * thème ne puisse être confondu avec un bloc du cœur ou d'un plugin.
 *
 * Ajouter une section = créer un dossier sous `blocks/` avec son `block.json`
 * et son `render.php`. Rien à déclarer ici.
 *
 * @package lcds
 */

require_once __DIR__ . '/enums/LcdsMediaShape.php';

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Enregistre tous les blocs du thème, un `block.json` par dossier.
 */
function lcds_register_blocks(): void
{
    $manifests = glob(get_template_directory() . '/blocks/*/block.json');

    if ($manifests === false) {
        return;
    }

    foreach ($manifests as $manifest) {
        register_block_type($manifest);
    }
}
add_action('init', 'lcds_register_blocks');

/**
 * Regroupe les blocs du thème dans leur propre catégorie de l'insérateur.
 *
 * Placée en tête : un contributeur cherche les blocs du site avant ceux du
 * cœur.
 *
 * @param array $categories Catégories déjà enregistrées.
 */
function lcds_register_block_category(array $categories): array
{
    array_unshift($categories, [
        'slug' => 'lcds',
        'title' => 'LCDS',
        'icon' => null,
    ]);

    return $categories;
}
add_filter('block_categories_all', 'lcds_register_block_category');

/**
 * Sert la feuille du thème à l'éditeur, pour que l'aperçu d'un bloc ressemble
 * au rendu public.
 *
 * Sans ceci les blocs s'affichent bien, mais sans aucun style : un contributeur
 * ne reconnaît pas la section qu'il modifie.
 *
 * WordPress INLINE le contenu du fichier dans l'éditeur puis préfixe ses
 * sélecteurs de `.editor-styles-wrapper`. Deux conséquences : la feuille ne peut
 * porter aucune `url()` relative, qui ne résoudrait pas depuis l'administration
 * — la feuille compilée n'en contient aucune, vérifié ; et le `body` du thème
 * est réécrit vers ce conteneur, donc appliqué comme attendu.
 *
 * Aucun correctif propre à l'éditeur n'est ajouté : les cotes du thème y sont
 * fluides et l'aperçu doit rester fidèle. Le mode épinglé du parcours, lui,
 * dépend d'une classe posée par le script, absent de l'éditeur — les étapes s'y
 * empilent, ce qui est exactement le rendu de repli.
 */
function lcds_editor_styles(): void
{
    add_theme_support('editor-styles');
    add_editor_style('dist/main.css');
}
add_action('after_setup_theme', 'lcds_editor_styles');
