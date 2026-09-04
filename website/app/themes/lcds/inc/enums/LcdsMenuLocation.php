<?php

/**
 * Emplacements de menu du thème — source unique de vérité.
 *
 * La valeur du cas est le slug de l'emplacement (`theme_location`), `label()`
 * porte à la fois le libellé affiché dans l'admin et le nom du menu créé au
 * premier démarrage.
 *
 * Ajouter un emplacement = ajouter un cas ici et ses bras dans `label()` ET
 * `items()`. Il est alors enregistré, et son menu créé puis garni, sans autre
 * intervention — voir menus.php.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

enum LcdsMenuLocation: string
{
    case Header = 'header-menu';
    case HeaderCta = 'header-cta-menu';
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
            self::HeaderCta => __("Bouton d'action de l'en-tête", 'lcds'),
            self::Footer => __('Menu pied de page', 'lcds'),
            self::Social => __('Réseaux sociaux', 'lcds'),
            self::Legal => __('Mentions légales', 'lcds'),
        };
    }

    /**
     * Entrées créées à l'amorçage, dans l'ordre d'affichage.
     *
     * Les liens valent `#` : les pages cibles n'existent pas encore. Une URL
     * vide est ACCEPTÉE par wp_update_nav_menu_item() — vérifié — mais rend un
     * `<a>` sans `href`, qui n'est pas un lien : ni focalisable, ni atteignable
     * au clavier. Le contributeur remplace la destination sans avoir à recréer
     * la navigation.
     *
     * Les trois emplacements du pied de page rendent un tableau VIDE : aucune
     * maquette ne les dessine à ce jour. Y mettre des entrées inventées ferait
     * apparaître en production une navigation que personne n'a validée.
     *
     * Pas de `default` dans le match : un emplacement ajouté sans décision
     * explicite lève UnhandledMatchError plutôt que de partir vide en silence.
     *
     * @return array<int, array{title: string, url: string}>
     */
    public function items(): array
    {
        return match ($this) {
            self::Header => [
                ['title' => __('Le cabinet', 'lcds'), 'url' => '#'],
                ['title' => __('L’équipe', 'lcds'), 'url' => '#'],
                ['title' => __('Les traitements', 'lcds'), 'url' => '#'],
                ['title' => __('Contact', 'lcds'), 'url' => '#'],
            ],
            self::HeaderCta => [
                ['title' => __('Prendre RDV', 'lcds'), 'url' => '#'],
            ],
            self::Footer, self::Social, self::Legal => [],
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
