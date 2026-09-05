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
require_once __DIR__ . '/enums/LcdsInfoIcon.php';
require_once __DIR__ . '/enums/LcdsFocalPoint.php';

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Retire l'interface de gestion des groupes de champs hors développement.
 *
 * La configuration des champs vit dans `acf-json/`, versionnée et déployée avec
 * le thème : elle se modifie en local, se relit en diff et se committe. Laisser
 * l'écran « Custom Fields » ouvert en préprod ou en production, c'est ouvrir la
 * seule porte par laquelle une configuration peut diverger du dépôt.
 *
 * Ce qui est mesuré, et qui explique le périmètre exact de ce garde-fou :
 * enregistrer un groupe depuis l'interface crée bien une copie EN BASE, mais
 * ACF continue de servir le JSON tant que celui-ci n'est pas plus ancien —
 * vérifié, le groupe reste rendu avec `ID = 0` et `local = 'json'`. La copie en
 * base n'est donc dangereuse que si le JSON n'arrive pas, ou arrive périmé.
 *
 * Ceci ne masque QUE la gestion des groupes. La saisie des valeurs par les
 * contributeurs n'est pas touchée.
 */
function lcds_hide_acf_admin(): bool
{
    return in_array(wp_get_environment_type(), ['local', 'development'], true);
}
add_filter('acf/settings/show_admin', 'lcds_hide_acf_admin');

/**
 * Retire du JSON écrit les clés propres à la machine.
 *
 * ACF pose `local` et `local_file` sur un groupe au moment où il le CHARGE
 * (`includes/local-json.php`), et les réécrit telles quelles quand on
 * l'enregistre depuis l'interface. `local_file` est un **chemin absolu** —
 * `/var/www/html/…` chez nous, autre chose ailleurs.
 *
 * Le dommage est limité : ACF réécrase la valeur à chaque chargement, donc rien
 * ne casse. Ce qu'elle abîme, c'est la RELECTURE EN DIFF — le fichier change
 * d'une machine à l'autre sans qu'aucun champ n'ait bougé, ce qui est
 * exactement ce qu'on cherche à éviter en versionnant la configuration.
 *
 * Accroché APRÈS l'écriture, et pas avant : `acf/pre_save_json_file` ne
 * concerne que les types de contenu ACF. Pour un groupe de champs,
 * `update_field_group()` appelle `save_file()` en direct et court-circuite ce
 * filtre — vérifié, la clé survivait.
 *
 * @param array $field_group Groupe qui vient d'être enregistré.
 */
function lcds_strip_local_json_paths(array $field_group): void
{
    if (! function_exists('acf_get_local_json_files')) {
        return;
    }

    $file = acf_get_local_json_files()[$field_group['key'] ?? ''] ?? null;

    if (! is_string($file) || ! is_writable($file)) {
        return;
    }

    $json = json_decode((string) file_get_contents($file), true);

    if (! is_array($json) || (! isset($json['local']) && ! isset($json['local_file']))) {
        return;
    }

    unset($json['local'], $json['local_file']);

    file_put_contents(
        $file,
        wp_json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
    );
}
add_action('acf/update_field_group', 'lcds_strip_local_json_paths', 20);

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
 * Enveloppe de get_sub_field(), pour les champs d'une rangée de contenu
 * flexible.
 *
 * Les sections de la page d'accueil sont les layouts d'un champ
 * `flexible_content` : dans la boucle `have_rows()`, leurs valeurs se lisent
 * par `get_sub_field()` et non par `get_field()`, qui remonterait au niveau de
 * la page et ne trouverait rien.
 *
 * @param string $selector Nom ou clé du sous-champ.
 */
function lcds_sub_field(string $selector): mixed
{
    return function_exists('get_sub_field') ? get_sub_field($selector) : null;
}

/**
 * Valeur d'un sous-champ, ramenée à une chaîne propre.
 *
 * @param string $selector Nom ou clé du sous-champ.
 */
function lcds_sub_field_text(string $selector): string
{
    $value = lcds_sub_field($selector);

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

/**
 * Alimente la liste d'icônes des informations pratiques depuis LcdsInfoIcon.
 *
 * @param array $field Définition du champ, telle qu'ACF la charge.
 */
function lcds_load_info_icons(array $field): array
{
    $field['choices'] = LcdsInfoIcon::choices();

    return $field;
}
add_filter('acf/load_field/key=field_lcds_infos_icone', 'lcds_load_info_icons');

/**
 * Alimente la liste des cadrages depuis LcdsFocalPoint.
 *
 * @param array $field Définition du champ, telle qu'ACF la charge.
 */
function lcds_load_focal_points(array $field): array
{
    $field['choices'] = LcdsFocalPoint::choices();

    return $field;
}
add_filter('acf/load_field/key=field_lcds_footer_cadrage', 'lcds_load_focal_points');
