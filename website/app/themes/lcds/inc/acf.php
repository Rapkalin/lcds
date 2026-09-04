<?php

/**
 * Intégration ACF.
 *
 * Les groupes de champs sont gérés en JSON local dans `acf-json/` du thème :
 * ACF y écrit et y lit tout seul dès que le dossier existe, aucun hook à poser.
 * Ils sont donc versionnés et relisibles en diff, tout en restant modifiables
 * depuis l'interface d'ACF.
 *
 * Tous les appels sont gardés : le thème doit se dégrader proprement si ACF est
 * absent ou désactivé, puisque c'est un plugin sous licence hors du dépôt.
 *
 * @package lcds
 */

require_once __DIR__ . '/enums/LcdsMediaShape.php';
require_once __DIR__ . '/enums/LcdsDotColor.php';

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Enveloppe de get_field() qui rend `null` quand ACF n'est pas là.
 *
 * Nommée `lcds_field` et non `get_field` pour ne pas masquer la fonction d'ACF.
 * Sans elle, chaque gabarit devrait répéter son propre `function_exists`.
 *
 * @param string           $selector Nom ou clé du champ.
 * @param int|string|false $post_id  Identifiant, 'option', ou false pour le
 *                                   contenu courant.
 */
function lcds_field(string $selector, int|string|false $post_id = false): mixed
{
    return function_exists('get_field') ? get_field($selector, $post_id) : null;
}

/**
 * Valeur d'un champ, ramenée à une chaîne propre.
 *
 * @param string $selector Nom ou clé du champ.
 */
function lcds_field_text(string $selector): string
{
    $value = lcds_field($selector);

    return is_scalar($value) ? trim((string) $value) : '';
}

/**
 * Identifiant d'attachement d'un champ image, quel que soit son format de retour.
 *
 * ACF rend un entier, un tableau ou une URL selon le réglage du champ. On ne
 * garde que l'identifiant : c'est la seule forme que lcds_render_image() sait
 * servir en WebP — voir readme/images.md.
 *
 * @param mixed $value Valeur brute d'un champ image.
 */
function lcds_attachment_id(mixed $value): int
{
    if (is_numeric($value)) {
        return (int) $value;
    }

    if (is_array($value) && isset($value['ID'])) {
        return (int) $value['ID'];
    }

    return 0;
}

/**
 * Alimente les listes de formes depuis LcdsMediaShape.
 *
 * Les choix ne sont PAS écrits dans le JSON du groupe : ils vivraient alors à
 * deux endroits, et une largeur ajoutée à l'enum n'apparaîtrait pas dans
 * l'administration. Le JSON ne déclare que le champ, l'enum en fournit le
 * contenu — voir inc/enums/LcdsMediaShape.php.
 *
 * @param array $field Définition du champ, telle qu'ACF la charge.
 */
function lcds_load_gallery_shapes(array $field): array
{
    $field['choices'] = LcdsMediaShape::choices([
        LcdsMediaShape::Large,
        LcdsMediaShape::Pair,
        LcdsMediaShape::Medium,
        LcdsMediaShape::Small,
    ]);

    return $field;
}
add_filter('acf/load_field/key=field_lcds_histoire_forme', 'lcds_load_gallery_shapes');

/**
 * Idem pour les visuels accompagnant une étape du parcours, qui n'ont que deux
 * formes possibles.
 *
 * @param array $field Définition du champ, telle qu'ACF la charge.
 */
function lcds_load_step_shapes(array $field): array
{
    $field['choices'] = LcdsMediaShape::choices([
        LcdsMediaShape::StepWide,
        LcdsMediaShape::StepNarrow,
    ]);

    return $field;
}
add_filter('acf/load_field/key=field_lcds_parcours_forme', 'lcds_load_step_shapes');

/**
 * Alimente les listes de couleurs de puce depuis LcdsDotColor.
 *
 * Accroché sur le NOM du champ et non sur sa clé : les trois blocs à étiquette
 * ont chacun leur propre clé, et une liste de couleurs recopiée trois fois est
 * exactement ce qu'on vient de supprimer.
 *
 * @param array $field Définition du champ, telle qu'ACF la charge.
 */
function lcds_load_dot_colors(array $field): array
{
    $field['choices'] = LcdsDotColor::choices();

    return $field;
}
add_filter('acf/load_field/name=puce', 'lcds_load_dot_colors');
