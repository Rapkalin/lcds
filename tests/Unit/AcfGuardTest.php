<?php

/**
 * ACF Pro est sous licence : il est HORS du dépôt et installé à la main sur
 * chaque environnement. Un gabarit qui appelle directement une de ses fonctions
 * tue donc la page dès qu'il n'est pas actif — et comme `display_errors` vaut 0
 * hors développement, ça donne une page blanche en 500, sans rien à l'écran.
 *
 * Arrivé en préproduction le 05/09/2026 :
 *
 *     PHP Fatal error: Uncaught Error: Call to undefined function have_rows()
 *     in .../themes/lcds/front-page.php:36
 *
 * Ce contrôle est statique et volontairement grossier : il exige qu'un fichier
 * qui appelle une fonction d'ACF en porte aussi la garde. Il ne prouve pas que
 * la garde entoure le bon appel — il rend simplement impossible d'en écrire un
 * sans y penser.
 */

const LCDS_THEME_DIR = __DIR__ . '/../../website/app/themes/lcds/';

/**
 * Fonctions d'ACF qui n'existent pas sans le plugin.
 *
 * @return array<int, string>
 */
function lcds_acf_functions(): array
{
    return [
        'have_rows', 'the_row', 'get_row_layout', 'get_row_index',
        'get_field', 'the_field', 'get_sub_field', 'the_sub_field',
        'get_field_object', 'update_field', 'acf_get_fields',
        'acf_add_options_page', 'acf_add_options_sub_page', 'acf_get_field_group',
    ];
}

/**
 * @return array<int, string>
 */
function lcds_theme_php_files(): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(LCDS_THEME_DIR));

    foreach ($iterator as $file) {
        $path = (string) $file;

        if (! str_ends_with($path, '.php')) {
            continue;
        }

        // `node_modules` et `dist` ne sont pas du code du thème.
        if (str_contains($path, '/node_modules/') || str_contains($path, '/dist/')) {
            continue;
        }

        $files[] = $path;
    }

    sort($files);

    return $files;
}

it('finds theme files to inspect', function () {
    expect(lcds_theme_php_files())->not->toBeEmpty();
});

it('never calls ACF without a guard in the same file', function (string $file) {
    $code = (string) file_get_contents($file);

    // inc/acf.php EST le fichier des enveloppes : c'est là que les appels
    // gardés vivent, par construction.
    if (str_ends_with($file, 'inc/acf.php')) {
        expect(true)->toBeTrue();

        return;
    }

    // Les commentaires sont retirés avant l'analyse : sans ça, une ligne qui
    // se contente de MENTIONNER `get_field()` pour expliquer pourquoi on ne
    // l'appelle pas fait échouer le contrôle. Constaté sur inc/contacts.php.
    $sansCommentaires = '';

    foreach (token_get_all($code) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        $sansCommentaires .= is_array($token) ? $token[1] : $token;
    }

    $appels = [];

    foreach (lcds_acf_functions() as $fonction) {
        if (preg_match('/(?<![\w$>])' . preg_quote($fonction, '/') . '\s*\(/', $sansCommentaires) === 1) {
            $appels[] = $fonction;
        }
    }

    if ($appels === []) {
        expect(true)->toBeTrue();

        return;
    }

    $garde = str_contains($sansCommentaires, 'lcds_has_acf()')
        || str_contains($sansCommentaires, 'function_exists(');

    expect($garde)
        ->toBeTrue(sprintf(
            '%s appelle %s sans garde : la page tombe en 500 si ACF n’est pas actif.',
            basename($file),
            implode(', ', $appels),
        ));
})->with(lcds_theme_php_files());
