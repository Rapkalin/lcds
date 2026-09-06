<?php

/**
 * Qui s'est connecté, et quand.
 *
 * Le journal vit dans une OPTION bornée, et non dans une table : le projet n'a
 * pas d'étape de migration au déploiement, et deux cents entrées ne justifient
 * pas d'en introduire une. La limite est assumée et documentée — deux
 * connexions dans la même seconde peuvent en perdre une, ce qui exclut cet
 * écran d'un usage d'audit.
 *
 * Aucune adresse IP n'est conservée : la question posée est « qui, quand »,
 * pas « d'où ».
 *
 * @package lcds
 */

require_once __DIR__ . '/enums/LcdsLoginLog.php';

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Option qui porte le journal.
 */
const LCDS_LOGIN_LOG_OPTION = 'lcds_login_log';

/**
 * Écran qui l'affiche.
 */
const LCDS_LOGIN_LOG_SCREEN = 'lcds-connexions';

/**
 * Enregistre une connexion réussie.
 *
 * @param string  $user_login Identifiant, tel que le cœur le passe.
 * @param WP_User $user       Compte connecté.
 */
function lcds_record_login(string $user_login, WP_User $user): void
{
    // `false` : l'option ne doit pas être autochargée à chaque requête du site.
    update_option(
        LCDS_LOGIN_LOG_OPTION,
        LcdsLoginLog::record(lcds_login_log(), $user->ID, $user_login, time()),
        false,
    );
}
add_action('wp_login', 'lcds_record_login', 10, 2);

/**
 * Le journal, tel qu'il est stocké.
 */
function lcds_login_log(): array
{
    $journal = get_option(LCDS_LOGIN_LOG_OPTION, []);

    return is_array($journal) ? $journal : [];
}

/**
 * Déclare l'écran sous « Comptes ».
 *
 * `add_submenu_page` et non `add_users_page` : cette dernière rattache l'écran
 * à `profile.php` dès que l'utilisateur n'a pas `edit_users`, ce qui le ferait
 * apparaître sous « Mon compte ».
 */
function lcds_register_login_screen(): void
{
    add_submenu_page(
        'users.php',
        __('Connexions', 'lcds'),
        __('Connexions', 'lcds'),
        'list_users',
        LCDS_LOGIN_LOG_SCREEN,
        'lcds_render_login_screen',
    );
}
add_action('admin_menu', 'lcds_register_login_screen');

/**
 * Affiche le journal.
 */
function lcds_render_login_screen(): void
{
    if (! current_user_can('list_users')) {
        wp_die(esc_html__("Vous n'avez pas accès à cet écran.", 'lcds'), '', ['response' => 403]);
    }

    $stocke = lcds_login_log();
    $journal = LcdsLoginLog::prune($stocke, time());

    // La rétention n'est tenue que si ce qui sort est RÉELLEMENT supprimé : le
    // filtrer à l'affichage laisserait la donnée en base.
    if ($journal !== $stocke) {
        update_option(LCDS_LOGIN_LOG_OPTION, $journal, false);
    }

    echo '<div class="wrap">';
    printf('<h1>%s</h1>', esc_html__('Connexions', 'lcds'));
    printf(
        '<p class="description">%s</p>',
        esc_html(sprintf(
            /* translators: %1$d : nombre de jours, %2$d : nombre d'entrées. */
            __('Les %1$d derniers jours, %2$d connexions au plus. Au-delà, les entrées sont supprimées.', 'lcds'),
            LcdsLoginLog::MAX_AGE_DAYS,
            LcdsLoginLog::MAX_ENTRIES,
        )),
    );

    if ($journal === []) {
        printf('<p>%s</p></div>', esc_html__('Aucune connexion enregistrée.', 'lcds'));

        return;
    }

    echo '<table class="widefat striped"><thead><tr>';
    printf('<th scope="col">%s</th>', esc_html__('Date', 'lcds'));
    printf('<th scope="col">%s</th>', esc_html__('Compte', 'lcds'));
    printf('<th scope="col">%s</th>', esc_html__('Rôle', 'lcds'));
    echo '</tr></thead><tbody>';

    $format = (string) get_option('date_format') . ' ' . (string) get_option('time_format');

    foreach ($journal as $entree) {
        $compte = get_userdata($entree['id']);

        echo '<tr>';
        printf('<td>%s</td>', esc_html((string) wp_date($format, $entree['time'])));
        printf('<td>%s</td>', esc_html(lcds_login_account_label($entree, $compte)));
        printf('<td>%s</td>', esc_html(lcds_login_role_label($compte)));
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

/**
 * Nom du compte, ou l'identifiant conservé si le compte n'existe plus.
 *
 * @param array          $entree Entrée du journal.
 * @param WP_User|false  $compte Compte résolu, ou false.
 */
function lcds_login_account_label(array $entree, WP_User|false $compte): string
{
    if (! $compte instanceof WP_User) {
        /* translators: %s : identifiant du compte supprimé. */
        return sprintf(__('%s (compte supprimé)', 'lcds'), (string) $entree['login']);
    }

    if ($compte->display_name === '' || $compte->display_name === $compte->user_login) {
        return $compte->user_login;
    }

    return sprintf('%s (%s)', $compte->display_name, $compte->user_login);
}

/**
 * Rôle courant du compte, traduit.
 *
 * @param WP_User|false $compte Compte résolu, ou false.
 */
function lcds_login_role_label(WP_User|false $compte): string
{
    if (! $compte instanceof WP_User) {
        return '—';
    }

    $role = (string) (array_values((array) $compte->roles)[0] ?? '');
    $noms = wp_roles()->get_names();

    return isset($noms[$role]) ? translate_user_role($noms[$role]) : '—';
}

/**
 * Ajoute la colonne « Dernière connexion » à la liste des comptes.
 *
 * Non triable : le journal vit dans une option, pas dans une méta, donc la
 * requête de la liste ne peut pas s'ordonner dessus.
 *
 * @param array $colonnes Colonnes, telles que le cœur les propose.
 */
function lcds_last_login_column(array $colonnes): array
{
    $colonnes[LCDS_LOGIN_LOG_SCREEN] = __('Dernière connexion', 'lcds');

    return $colonnes;
}
add_filter('manage_users_columns', 'lcds_last_login_column');

/**
 * Remplit la colonne.
 *
 * Le cœur concatène ce retour SANS l'échapper : c'est à nous de le faire.
 *
 * @param string $sortie   Contenu produit jusqu'ici.
 * @param string $colonne  Colonne en cours de rendu.
 * @param int    $user_id  Compte de la ligne.
 */
function lcds_last_login_cell(string $sortie, string $colonne, int $user_id): string
{
    if ($colonne !== LCDS_LOGIN_LOG_SCREEN) {
        return $sortie;
    }

    $instant = lcds_last_logins()[$user_id] ?? null;

    if ($instant === null) {
        // Un tiret seul se lit « tiret » à la synthèse vocale : le sens part
        // dans un texte réservé aux lecteurs d'écran.
        return '<span aria-hidden="true">&mdash;</span>'
            . '<span class="screen-reader-text">' . esc_html__('Jamais connecté', 'lcds') . '</span>';
    }

    $format = (string) get_option('date_format') . ' ' . (string) get_option('time_format');

    return esc_html((string) wp_date($format, $instant));
}
add_filter('manage_users_custom_column', 'lcds_last_login_cell', 10, 3);

/**
 * Dernière connexion de chaque compte, calculée une seule fois par requête.
 */
function lcds_last_logins(): array
{
    static $derniers = null;

    if (! is_array($derniers)) {
        $derniers = LcdsLoginLog::latestPerUser(LcdsLoginLog::prune(lcds_login_log(), time()));
    }

    return $derniers;
}
