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
 *  - un menu qui porte DÉJÀ au moins une entrée n'est jamais retouché : ses
 *    entrées par défaut ne sont posées que dans un menu vide ;
 *  - un menu supprimé ensuite n'est pas ressuscité : la version d'amorçage est
 *    déjà enregistrée en base.
 *
 * Ajouter un emplacement plus tard : ajouter le cas dans LcdsMenuLocation, puis
 * incrémenter MENUS_SEED_VERSION pour déclencher un nouvel amorçage — contrôlé,
 * et sans toucher à l'existant.
 *
 * Pourquoi les entrées sont versionnées et non contribuées : `wp_nav_menu()` est
 * appelé avec `fallback_cb => false`, donc un menu vide ne rend RIEN. Sans ces
 * entrées, un environnement fraîchement déployé sortirait un en-tête sans
 * navigation jusqu'à ce que quelqu'un la saisisse à la main — constaté, c'était
 * l'état du dépôt avant cette mécanique.
 *
 * L'amorçage est aussi joué par WP-CLI après un déploiement (voir le workflow
 * `deploy.yml`) : accroché au seul `admin_init`, il attendrait la première
 * visite d'un administrateur pour que le site ait une navigation.
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
const LCDS_MENUS_SEED_VERSION = 3;

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

    lcds_seed_menu_items($menu_id, $location->items());

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

/**
 * Pose les entrées par défaut d'un menu, et seulement s'il est vide.
 *
 * La condition « vide » est le garde-fou central : un contributeur qui a
 * remanié sa navigation ne doit pas la voir se dédoubler au prochain
 * incrément de MENUS_SEED_VERSION.
 *
 * @param array<int, array{title: string, url: string}> $items Entrées à créer.
 */
function lcds_seed_menu_items(int $menu_id, array $items): void
{
    if ($items === []) {
        return;
    }

    // Les brouillons comptent : une entrée créée puis dépubliée reste le
    // travail de quelqu'un.
    $existing = wp_get_nav_menu_items($menu_id, ['post_status' => 'publish,draft']);

    if (is_array($existing) && $existing !== []) {
        return;
    }

    foreach ($items as $index => $item) {
        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title' => $item['title'],
            'menu-item-url' => $item['url'],
            'menu-item-type' => 'custom',
            'menu-item-status' => 'publish',
            'menu-item-position' => $index + 1,
        ]);
    }
}
