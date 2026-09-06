<?php

/**
 * Les champs que « Mon compte » garde pour un contributeur.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Écran « Mon compte », réduit à ce dont un contributeur se sert.
 *
 * Les cas portent la classe que le cœur pose sur la LIGNE du formulaire —
 * `wp-admin/user-edit.php` en met une sur chacune. C'est le seul point
 * d'accroche : le cœur n'expose aucun filtre par champ.
 *
 * L'enum liste ce qui RESTE, et non ce qui part : ajouter une ligne au cœur ne
 * doit pas la faire apparaître par défaut chez le contributeur, et la liste des
 * champs gardés est celle que l'utilisateur a validée.
 */
enum LcdsProfileField: string
{
    case AdminColor = 'user-admin-color-wrap';
    case Toolbar = 'user-admin-bar-front-wrap';
    case Login = 'user-user-login-wrap';
    case FirstName = 'user-first-name-wrap';
    case LastName = 'user-last-name-wrap';
    case Nickname = 'user-nickname-wrap';
    case Email = 'user-email-wrap';

    /**
     * Les lignes du cœur retirées de l'écran.
     *
     * Énumérées et non déduites : le cœur ne donne aucun moyen de lister ses
     * propres lignes, et une déduction se tromperait en silence.
     */
    public static function hiddenRows(): array
    {
        return [
            'user-rich-editing-wrap',
            'user-syntax-highlighting-wrap',
            'user-comment-shortcuts-wrap',
            'user-infinite-scrolling-wrap',
            'user-language-wrap',
            'user-display-name-wrap',
            'user-url-wrap',
        ];
    }

    /**
     * Les lignes traitées AILLEURS que par la feuille de style.
     *
     * Chacune pour une raison différente, et c'est pourquoi elles sont nommées :
     * une ligne qu'on croit masquée alors qu'elle ne l'est que par accident se
     * découvre à la première mise à jour du cœur.
     */
    public static function rowsHandledElsewhere(): array
    {
        return [
            // Le cœur ne les rend pas sur SON PROPRE profil.
            'user-role-wrap',
            'user-super-admin-wrap',
            // Retirée par le filtre `additional_capabilities_display`.
            'user-capabilities-wrap',

            // Dans « À propos de vous », masquée avec toute la section.
            'user-description-wrap',
            // Gardée : elle appartient à « Gestion du compte ».
            'user-sessions-wrap',
        ];
    }

    /**
     * Les lignes gardées.
     */
    public static function keptRows(): array
    {
        return array_map(static fn(self $champ): string => $champ->value, self::cases());
    }
}
