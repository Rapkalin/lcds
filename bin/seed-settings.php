<?php

/**
 * Amorçage des réglages du site — joué par bin/init.sh via `wp eval-file`.
 *
 * Garnit le pied de page, dont le contenu vit dans une page d'options ACF et
 * n'appartient donc à aucune page. La copie vient du PDF de maquette.
 *
 * IDEMPOTENT. Un pied de page déjà garni n'est jamais réécrit : le contenu
 * saisi par un contributeur ne doit pas disparaître au redémarrage d'un
 * conteneur. Passer `force` en argument POSITIONNEL pour le recréer.
 *
 * @package lcds
 */

if (! defined('WP_CLI')) {
    return;
}

if (! function_exists('acf_get_fields') || ! function_exists('update_field')) {
    WP_CLI::warning('ACF est absent : amorçage des réglages impossible.');

    return;
}

$force = in_array('force', (array) ($args ?? []), true);

if (! $force && is_array(get_field('blocs', 'option')) && get_field('blocs', 'option') !== []) {
    WP_CLI::log('==> [init] Réglages du pied de page déjà en place.');

    return;
}

$media_map = get_option('lcds_demo_media');
$media_map = is_array($media_map) ? $media_map : [];

// Les maquettes ne portent aucune destination : les pages cibles n'existent pas
// encore. Un `#` rend le bouton visible — un lien VIDE fait disparaître le
// composant CTA, qui refuse de produire un lien mort.
$stub = '#';
$link = static fn(string $title): array => ['title' => $title, 'url' => $stub, 'target' => ''];

update_field('blocs', [
    [
        'titre' => 'Prendre le 1er rendez-vous',
        'liens' => [
            ['lien' => $link('voir sur doctolib')],
            ['lien' => $link('appeler')],
        ],
    ],
    [
        'titre' => 'Rester informé',
        'liens' => [['lien' => $link('s’inscrire à la newsletter')]],
    ],
    [
        'titre' => 'Une urgence ?',
        'liens' => [['lien' => $link('connaître nos conseils')]],
    ],
], 'option');

update_field('surtitre', 'cabinet d’orthodontie lcds', 'option');
update_field('adresse', "<p>2 place Saint-Maurice</p>\n<p>38200 Vienne</p>", 'option');

// `%s` est remplacé par l'année courante au rendu : une année en dur serait
// fausse au 1er janvier.
update_field('copyright', 'Copyright © %s LCDS', 'option');

// Le visuel révélé n'existe que si les visuels de démonstration sont en place.
if (isset($media_map['gallery-5'])) {
    update_field('visuel', (int) $media_map['gallery-5'], 'option');
}

WP_CLI::log('==> [init] Réglages du pied de page amorcés (3 blocs d’appel).');
