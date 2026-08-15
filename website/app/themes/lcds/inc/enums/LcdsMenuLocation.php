<?php

/**
 * Emplacements de menu du thème — source unique de vérité.
 *
 * La valeur du cas est le slug de l'emplacement (`theme_location`), `label()`
 * porte à la fois le libellé affiché dans l'admin et le nom du menu créé au
 * premier démarrage.
 *
 * Ajouter un emplacement = ajouter un cas ici et son bras dans `label()`. Il est
 * alors enregistré, et son menu créé, sans autre intervention — voir menus.php.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

enum LcdsMenuLocation: string
{
    case Header = 'header-menu';
    case Footer = 'footer-menu';
    case Social = 'social-menu';
    case Legal = 'legal-menu';

    /**
     * Libellé de l'emplacement dans l'admin, et nom du menu créé par défaut.
     *
     * Pas de `default` dans le match : un cas ajouté sans libellé lève
     * UnhandledMatchError au lieu de passer inaperçu.
     */
    public function label(): string
    {
        return match ($this) {
            self::Header => __('Menu principal', 'lcds'),
            self::Footer => __('Menu pied de page', 'lcds'),
            self::Social => __('Réseaux sociaux', 'lcds'),
            self::Legal => __('Mentions légales', 'lcds'),
        };
    }

    /**
     * Emplacements au format attendu par register_nav_menus().
     *
     * @return array<string, string>
     */
    public static function registry(): array
    {
        $locations = [];

        foreach (self::cases() as $location) {
            $locations[$location->value] = $location->label();
        }

        return $locations;
    }
}
