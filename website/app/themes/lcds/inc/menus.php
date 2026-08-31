<?php

/**
 * Enregistrement des emplacements de menu et création automatique des menus.
 *
 * Objectif : un contributeur ne doit jamais tomber sur l'écran « Créez votre
 * premier menu ». Chaque emplacement déclaré dans LcdsMenuLocation reçoit un
 * menu vide au premier passage en admin, sur tous les environnements.
 *
 * Garde-fous, dans cet ordre d'importance :
 *  - un menu portant déjà ce nom n'est JAMAIS recréé ni modifié ;
 *  - un emplacement déjà pourvu n'est JAMAIS réassigné : le choix du
 *    contributeur prime toujours sur le code ;
 *  - les menus sont créés VIDES — les remplir est un travail éditorial ;
 *  - un menu supprimé ensuite n'est pas ressuscité : la version d'amorçage est
 *    déjà enregistrée en base.
 *
 * Ajouter un emplacement plus tard : ajouter le cas dans LcdsMenuLocation, puis
 * incrémenter MENUS_SEED_VERSION pour déclencher un nouvel amorçage — contrôlé,
 * et sans toucher à l'existant.
 *
 * @package lcds
 */

require_once __DIR__ . '/enums/LcdsMenuLocation.php';

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Version d'amorçage. À incrémenter pour rejouer la création sur tous les
 * environnements (après l'ajout d'un emplacement, par exemple).
 */
const LCDS_MENUS_SEED_VERSION = 2;

/**
 * Option stockant la version d'amorçage déjà appliquée à cette base.
 */
const LCDS_MENUS_SEED_OPTION = 'lcds_menus_seed_version';

/**
 * Déclare les emplacements de menu à partir de l'enum.
 */
function lcds_register_menus(): void
{
    register_nav_menus(LcdsMenuLocation::registry());
}
add_action('after_setup_theme', 'lcds_register_menus');

/**
 * Crée les menus manquants et les rattache à leur emplacement.
 *
 * Branché sur admin_init : s'exécute à la première requête d'admin, puis se
 * réduit à une lecture d'option à chaque appel suivant. Le front n'est jamais
 * ralenti par cette vérification.
 */
function lcds_seed_default_menus(): void
{
    if ((int) get_option(LCDS_MENUS_SEED_OPTION, 0) >= LCDS_MENUS_SEED_VERSION) {
        return;
    }

    foreach (LcdsMenuLocation::cases() as $location) {
        lcds_seed_menu($location);
    }

    update_option(LCDS_MENUS_SEED_OPTION, LCDS_MENUS_SEED_VERSION);
}
add_action('admin_init', 'lcds_seed_default_menus');

/**
 * Crée le menu d'un emplacement s'il manque, et l'assigne si l'emplacement est
 * encore libre.
 */
function lcds_seed_menu(LcdsMenuLocation $location): void
{
    $menu_id = lcds_seed_menu_object($location->label());

    if ($menu_id === 0) {
        return;
    }

    $locations = get_theme_mod('nav_menu_locations', []);

    // Un emplacement déjà pourvu reste tel quel : écraser ici reviendrait à
    // défaire le travail d'un contributeur à chaque déploiement.
    if (empty($locations[$location->value])) {
        $locations[$location->value] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }
}

/**
 * Retourne l'identifiant du menu portant ce nom, en le créant vide si besoin.
 *
 * @return int L'identifiant du menu, 0 en cas d'échec.
 */
function lcds_seed_menu_object(string $name): int
{
    $menu = wp_get_nav_menu_object($name);

    if ($menu) {
        return (int) $menu->term_id;
    }

    $menu_id = wp_create_nav_menu($name);

    return is_wp_error($menu_id) ? 0 : (int) $menu_id;
}
