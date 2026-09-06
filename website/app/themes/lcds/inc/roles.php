<?php

/**
 * Rôle de contribution et périmètre de l'administration.
 *
 * Un contributeur ne doit voir que ce qu'il a à faire : les pages, les médias,
 * les menus, le personnalisateur, la configuration du site et son propre profil.
 * Tout le reste est retiré du menu ET refusé à l'accès — masquer une entrée ne
 * protège rien, l'URL reste tapable.
 *
 * @package lcds
 */

require_once __DIR__ . '/enums/LcdsAdminScreen.php';
require_once __DIR__ . '/enums/LcdsProfileField.php';

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Identifiant du rôle.
 */
const LCDS_CONTRIBUTOR_ROLE = 'lcds_contributeur';

/**
 * Capacité qui ouvre l'écran « Réglages → Configuration ».
 *
 * Une capacité DÉDIÉE, et non `manage_options` : celle-ci ouvre les sept écrans
 * de Réglages du cœur — dont Lecture, qui désigne la page d'accueil, et
 * Permaliens — ainsi que `options.php`, l'éditeur brut de toutes les options en
 * base. La donner à un contributeur revient à en faire un administrateur.
 *
 * WordPress promeut le premier sous-menu accessible au rang de parent quand le
 * parent ne l'est pas (wp-admin/includes/menu.php) : « Réglages » apparaît donc
 * avec « Configuration » pour seul contenu.
 */
const LCDS_SETTINGS_CAP = 'lcds_manage_settings';

/**
 * Version du rôle. À incrémenter pour rejouer sa définition sur tous les
 * environnements après un changement de capacités.
 */
const LCDS_ROLE_VERSION = 1;

/**
 * Option stockant la version de rôle déjà appliquée à cette base.
 */
const LCDS_ROLE_OPTION = 'lcds_role_version';

/**
 * Crée ou met à jour le rôle de contribution.
 *
 * Bâti sur les capacités de l'éditeur — qui n'a ni `manage_options`, ni
 * `activate_plugins`, ni `switch_themes` — auxquelles s'ajoutent l'accès aux
 * menus et à la configuration du site.
 *
 * `edit_theme_options` est indispensable pour les menus et le personnalisateur,
 * mais il ouvre AUSSI l'éditeur de site et la bibliothèque de polices : ces
 * deux écrans sont refusés par le garde-fou d'accès.
 */
function lcds_register_contributor_role(): void
{
    if ((int) get_option(LCDS_ROLE_OPTION, 0) >= LCDS_ROLE_VERSION) {
        return;
    }

    $editor = get_role('editor');

    if (! $editor instanceof WP_Role) {
        return;
    }

    $capabilities = array_filter($editor->capabilities);
    $capabilities['edit_theme_options'] = true;
    $capabilities[LCDS_SETTINGS_CAP] = true;

    remove_role(LCDS_CONTRIBUTOR_ROLE);
    add_role(LCDS_CONTRIBUTOR_ROLE, __('Contributeur LCDS', 'lcds'), $capabilities);

    // L'administrateur doit garder l'accès à la configuration du site.
    $administrator = get_role('administrator');

    if ($administrator instanceof WP_Role) {
        $administrator->add_cap(LCDS_SETTINGS_CAP);
    }

    update_option(LCDS_ROLE_OPTION, LCDS_ROLE_VERSION);
}
add_action('admin_init', 'lcds_register_contributor_role');

/**
 * L'utilisateur courant est-il soumis au périmètre restreint ?
 *
 * Le périmètre suit le RÔLE et non l'absence d'une capacité : un administrateur
 * qui perdrait une capacité par accident ne doit pas se retrouver enfermé.
 */
function lcds_is_restricted_user(): bool
{
    $user = wp_get_current_user();

    return $user->exists() && in_array(LCDS_CONTRIBUTOR_ROLE, (array) $user->roles, true);
}

/**
 * Retire du menu tout ce qui sort du périmètre.
 */
function lcds_trim_admin_menu(): void
{
    if (! lcds_is_restricted_user()) {
        return;
    }

    foreach (LcdsAdminScreen::forbiddenMenus() as $slug) {
        remove_menu_page($slug);
    }

    global $menu;

    foreach ((array) $menu as $entree) {
        $slug = (string) ($entree[2] ?? '');

        foreach (LcdsAdminScreen::forbiddenMenuPrefixes() as $prefixe) {
            if (str_starts_with($slug, $prefixe)) {
                remove_menu_page($slug);
            }
        }
    }

    foreach (LcdsAdminScreen::forbiddenSubmenus() as [$parent, $slug]) {
        remove_submenu_page($parent, $slug);
    }
}
add_action('admin_menu', 'lcds_trim_admin_menu', 999);

/**
 * Refuse l'accès aux écrans hors périmètre.
 *
 * Le masquage du menu n'est que cosmétique : sans ce garde-fou, un contributeur
 * atteint `themes.php` ou `options-permalink.php` en tapant l'URL.
 */
function lcds_guard_admin_screens(): void
{
    if (wp_doing_ajax() || ! lcds_is_restricted_user()) {
        return;
    }

    global $pagenow;

    if (LcdsAdminScreen::isAllowed((string) $pagenow, $_GET)) {
        return;
    }

    wp_die(
        esc_html__('Vous n’avez pas accès à cette page.', 'lcds'),
        esc_html__('Accès refusé', 'lcds'),
        ['response' => 403, 'back_link' => true],
    );
}
add_action('admin_init', 'lcds_guard_admin_screens', 1);

/**
 * Ne laisse au tableau de bord que « D'un coup d'œil ».
 *
 * Les autres blocs parlent de WordPress, pas du site : activité de publication,
 * brouillon rapide, actualités du projet, santé du site.
 */
function lcds_trim_dashboard(): void
{
    global $wp_meta_boxes;

    foreach (['normal', 'side', 'column3', 'column4'] as $contexte) {
        foreach (['high', 'core', 'default', 'low'] as $priorite) {
            $boites = $wp_meta_boxes['dashboard'][$contexte][$priorite] ?? [];

            foreach (array_keys($boites) as $identifiant) {
                if ($identifiant !== 'dashboard_right_now') {
                    unset($wp_meta_boxes['dashboard'][$contexte][$priorite][$identifiant]);
                }
            }
        }
    }
}
add_action('wp_dashboard_setup', 'lcds_trim_dashboard', 999);

/**
 * Les rôles proposés dans l'administration.
 *
 * Deux seulement : administrateur et contributeur LCDS. Les rôles par défaut de
 * WordPress et ceux qu'ajoutent les extensions restent DÉCLARÉS — les retirer
 * casserait un compte qui les porte — mais ils ne sont plus attribuables.
 *
 * @param array $roles Rôles attribuables, tels que WordPress les propose.
 */
function lcds_assignable_roles(array $roles): array
{
    $gardes = ['administrator' => true, LCDS_CONTRIBUTOR_ROLE => true];

    // Le rôle du compte ÉDITÉ reste proposé, même hors des deux : sans son
    // option dans la liste, le navigateur en sélectionne une autre et
    // l'enregistrement change le rôle sans que personne l'ait demandé.
    foreach (lcds_edited_user_roles() as $role) {
        $gardes[$role] = true;
    }

    return array_intersect_key($roles, $gardes);
}
add_filter('editable_roles', 'lcds_assignable_roles');

/**
 * Rôles du compte que l'écran courant modifie, s'il y en a un.
 */
function lcds_edited_user_roles(): array
{
    $demande = $_REQUEST['user_id'] ?? null;
    $user = is_scalar($demande) ? get_userdata(absint($demande)) : false;

    return $user instanceof WP_User ? array_values((array) $user->roles) : [];
}

/**
 * Réduit « Mon compte » aux champs dont un contributeur se sert.
 *
 * Accroché à `load-profile.php` et non à `user-edit.php` : un administrateur
 * qui modifie un contributeur garde le formulaire entier.
 */
function lcds_trim_profile(): void
{
    if (! lcds_is_restricted_user()) {
        return;
    }

    add_filter('user_contactmethods', '__return_empty_array');
    add_filter('additional_capabilities_display', '__return_false');
    add_filter('wp_is_application_passwords_available_for_user', '__return_false');

    // Les sections posées par les extensions — Yoast en met une sur chaque
    // profil. Elles sortent du périmètre au même titre que le reste.
    remove_all_actions('personal_options');
    remove_all_actions('profile_personal_options');
    remove_all_actions('show_user_profile');

    add_action('admin_head', 'lcds_profile_styles');
}
add_action('load-profile.php', 'lcds_trim_profile');

/**
 * Masque les lignes hors périmètre.
 *
 * Par une feuille de style, faute de mieux : le cœur n'expose aucun filtre par
 * champ. C'est de la MISE EN FORME, pas une barrière — un champ masqué reste
 * soumettable. Ça n'ouvre rien : ce sont les données du compte lui-même, qu'un
 * contributeur a le droit de modifier. Ce qui relève de la sécurité est ailleurs
 * — capacités du rôle et garde d'écrans.
 */
function lcds_profile_styles(): void
{
    $lignes = implode(",\n", array_map(
        static fn(string $classe): string => '#your-profile .' . $classe,
        LcdsProfileField::hiddenRows(),
    ));

    // Deux règles et non une : un navigateur qui ignore `:has()` jette la LISTE
    // ENTIÈRE de sélecteurs, y compris les lignes ordinaires ci-dessus.
    //
    // La section « À propos de vous » n'a ni conteneur ni identifiant : elle se
    // désigne par ce qu'elle contient, son titre par le tableau qui le suit.
    $sections = "#your-profile h2:has(+ table .user-description-wrap),\n"
        . "#your-profile table:has(.user-description-wrap)";

    printf(
        '<style>%s { display: none; } %s { display: none; } #your-profile .application-passwords { display: none; }</style>',
        $lignes,
        $sections,
    );
}
