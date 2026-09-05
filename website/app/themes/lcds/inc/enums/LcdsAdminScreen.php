<?php

/**
 * Périmètre de l'administration pour le rôle de contribution — source unique.
 *
 * La décision est prise par une ALLOW-LIST et non par une liste d'interdits :
 * un écran ajouté par une future extension est refusé par défaut, alors qu'une
 * liste noire l'aurait laissé passer sans que personne ne s'en aperçoive.
 *
 * `isAllowed()` est une fonction PURE : elle ne lit ni l'utilisateur courant ni
 * la base. C'est ce qui la rend vérifiable par la suite de tests, là où le
 * garde-fou lui-même ne l'est pas.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

final class LcdsAdminScreen
{
    /**
     * Écrans toujours autorisés, quels que soient les paramètres.
     *
     * `admin-ajax.php`, `admin-post.php` et `async-upload.php` ne sont pas des
     * écrans : les refuser casserait l'éditeur, le téléversement et la
     * médiathèque sans rien protéger.
     */
    private const ALWAYS = [
        'index.php',
        'profile.php',
        'upload.php',
        'media-new.php',
        'media-upload.php',
        'nav-menus.php',
        'customize.php',
        'admin-ajax.php',
        'admin-post.php',
        'async-upload.php',
    ];

    /**
     * Types de contenu qu'un contributeur peut éditer.
     */
    private const POST_TYPES = ['page', 'attachment'];

    /**
     * L'écran demandé est-il dans le périmètre ?
     *
     * @param string $pagenow Fichier d'administration demandé.
     * @param array  $query   Paramètres de la requête.
     */
    public static function isAllowed(string $pagenow, array $query = []): bool
    {
        if (in_array($pagenow, self::ALWAYS, true)) {
            return true;
        }

        // La configuration du site, et elle seule, parmi les écrans de Réglages.
        if ($pagenow === 'options-general.php') {
            return ($query['page'] ?? '') === 'lcds-settings';
        }

        // Listes et édition de contenu : le type demandé décide.
        if (in_array($pagenow, ['edit.php', 'post-new.php'], true)) {
            $type = (string) ($query['post_type'] ?? 'post');

            return in_array($type, self::POST_TYPES, true);
        }

        // `post.php` ne porte pas le type dans l'URL : l'appelant le résout.
        if ($pagenow === 'post.php') {
            $type = (string) ($query['post_type'] ?? '');

            return $type === '' || in_array($type, self::POST_TYPES, true);
        }

        return false;
    }

    /**
     * Préfixes de slug retirés du menu, quel que soit le reste.
     *
     * Le menu de Yoast s'enregistre sous `wpseo_page_academy` — un slug qui
     * porte le compteur de notifications et change donc d'une version à
     * l'autre. On retire par PRÉFIXE : le référencement d'une page se saisit
     * dans la boîte sous le contenu, qui reste accessible ; ce menu-ci ne parle
     * que du plugin (académie, entraînements, offres de mise à niveau).
     *
     * @return array<int, string>
     */
    public static function forbiddenMenuPrefixes(): array
    {
        return ['wpseo'];
    }

    /**
     * Entrées de premier niveau retirées du menu.
     *
     * @return array<int, string>
     */
    public static function forbiddenMenus(): array
    {
        return [
            'edit.php',
            'edit-comments.php',
            'tools.php',
            'plugins.php',
            'users.php',
            // PAS `options-general.php` : le retirer emporterait « Réglages →
            // Configuration » avec lui. Les sept écrans du cœur exigent
            // `manage_options`, que le rôle n'a pas — ils ne s'affichent donc
            // pas, et WordPress promeut notre sous-entrée au rang de parent.
        ];
    }

    /**
     * Sous-entrées retirées du menu, par parent.
     *
     * `edit_theme_options` est indispensable aux menus et au personnalisateur,
     * mais il ouvre aussi l'éditeur de site et la bibliothèque de polices.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public static function forbiddenSubmenus(): array
    {
        return [
            ['themes.php', 'themes.php'],
            ['themes.php', 'site-editor.php'],
            ['themes.php', 'font-library.php'],
            ['themes.php', 'widgets.php'],
            ['themes.php', 'theme-editor.php'],
            // Les compositions passent par le même écran que l'éditeur de site,
            // avec un paramètre : le slug diffère, il faut le retirer à part.
            ['themes.php', 'site-editor.php?p=%2Fpattern'],
            ['themes.php', 'site-editor.php?p=/pattern'],
        ];
    }
}
