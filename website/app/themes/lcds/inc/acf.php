<?php

/**
 * Intégration ACF.
 *
 * Les groupes de champs sont gérés en JSON local dans `acf-json/` du thème :
 * ACF y écrit et y lit tout seul dès que le dossier existe, aucun hook à poser.
 * Ils sont donc versionnés et relisibles en diff, tout en restant modifiables
 * depuis l'interface d'ACF.
 *
 * Tous les appels sont gardés : le thème doit se dégrader proprement si ACF est
 * absent ou désactivé, puisque c'est un plugin sous licence hors du dépôt.
 *
 * @package lcds
 */

require_once __DIR__ . '/enums/LcdsMediaShape.php';
require_once __DIR__ . '/enums/LcdsDotColor.php';
require_once __DIR__ . '/enums/LcdsInfoIcon.php';
require_once __DIR__ . '/enums/LcdsFocalPoint.php';
require_once __DIR__ . '/enums/LcdsAdviceSection.php';
require_once __DIR__ . '/enums/LcdsVideoSource.php';
require_once __DIR__ . '/enums/LcdsWeekDay.php';
require_once __DIR__ . '/youtube.php';

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Retire l'interface de gestion des groupes de champs hors développement.
 *
 * La configuration des champs vit dans `acf-json/`, versionnée et déployée avec
 * le thème : elle se modifie en local, se relit en diff et se committe. Laisser
 * l'écran « Custom Fields » ouvert en préprod ou en production, c'est ouvrir la
 * seule porte par laquelle une configuration peut diverger du dépôt.
 *
 * Ce qui est mesuré, et qui explique le périmètre exact de ce garde-fou :
 * enregistrer un groupe depuis l'interface crée bien une copie EN BASE, mais
 * ACF continue de servir le JSON tant que celui-ci n'est pas plus ancien —
 * vérifié, le groupe reste rendu avec `ID = 0` et `local = 'json'`. La copie en
 * base n'est donc dangereuse que si le JSON n'arrive pas, ou arrive périmé.
 *
 * Ceci ne masque QUE la gestion des groupes. La saisie des valeurs par les
 * contributeurs n'est pas touchée.
 */
function lcds_hide_acf_admin(): bool
{
    return in_array(wp_get_environment_type(), ['local', 'development'], true);
}
add_filter('acf/settings/show_admin', 'lcds_hide_acf_admin');

/**
 * Retire du JSON écrit les clés propres à la machine.
 *
 * ACF pose `local` et `local_file` sur un groupe au moment où il le CHARGE
 * (`includes/local-json.php`), et les réécrit telles quelles quand on
 * l'enregistre depuis l'interface. `local_file` est un **chemin absolu** —
 * `/var/www/html/…` chez nous, autre chose ailleurs.
 *
 * Le dommage est limité : ACF réécrase la valeur à chaque chargement, donc rien
 * ne casse. Ce qu'elle abîme, c'est la RELECTURE EN DIFF — le fichier change
 * d'une machine à l'autre sans qu'aucun champ n'ait bougé, ce qui est
 * exactement ce qu'on cherche à éviter en versionnant la configuration.
 *
 * Accroché APRÈS l'écriture, et pas avant : `acf/pre_save_json_file` ne
 * concerne que les types de contenu ACF. Pour un groupe de champs,
 * `update_field_group()` appelle `save_file()` en direct et court-circuite ce
 * filtre — vérifié, la clé survivait.
 *
 * @param array $field_group Groupe qui vient d'être enregistré.
 */
function lcds_strip_local_json_paths(array $field_group): void
{
    if (! function_exists('acf_get_local_json_files')) {
        return;
    }

    $file = acf_get_local_json_files()[$field_group['key'] ?? ''] ?? null;

    if (! is_string($file) || ! is_writable($file)) {
        return;
    }

    $json = json_decode((string) file_get_contents($file), true);

    if (! is_array($json) || (! isset($json['local']) && ! isset($json['local_file']))) {
        return;
    }

    unset($json['local'], $json['local_file']);

    file_put_contents(
        $file,
        wp_json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
    );
}
add_action('acf/update_field_group', 'lcds_strip_local_json_paths', 20);

/**
 * ACF est-il disponible ?
 *
 * Le thème doit se dégrader proprement sans lui : c'est un plugin sous licence,
 * hors du dépôt, installé à la main sur chaque environnement. Un gabarit qui
 * appelle `have_rows()` sans cette garde tue la page — constaté en préproduction
 * le 05/09/2026, `Call to undefined function have_rows()` dans front-page.php,
 * page blanche en 500 puisque `display_errors` vaut 0 hors développement.
 *
 * `have_rows` et non `get_field` : c'est la fonction qu'un gabarit de contenu
 * flexible appelle en premier, donc celle qui tombe en premier.
 */
function lcds_has_acf(): bool
{
    return function_exists('have_rows') && function_exists('the_row');
}

/**
 * Enveloppe de get_field() qui rend `null` quand ACF n'est pas là.
 *
 * Nommée `lcds_field` et non `get_field` pour ne pas masquer la fonction d'ACF.
 * Sans elle, chaque gabarit devrait répéter son propre `function_exists`.
 *
 * @param string           $selector Nom ou clé du champ.
 * @param int|string|false $post_id  Identifiant, 'option', ou false pour le
 *                                   contenu courant.
 */
function lcds_field(string $selector, int|string|false $post_id = false): mixed
{
    return function_exists('get_field') ? get_field($selector, $post_id) : null;
}

/**
 * Valeur d'un champ, ramenée à une chaîne propre.
 *
 * @param string $selector Nom ou clé du champ.
 */
function lcds_field_text(string $selector): string
{
    $value = lcds_field($selector);

    return is_scalar($value) ? trim((string) $value) : '';
}

/**
 * Enveloppe de get_sub_field(), pour les champs d'une rangée de contenu
 * flexible.
 *
 * Les sections de la page d'accueil sont les layouts d'un champ
 * `flexible_content` : dans la boucle `have_rows()`, leurs valeurs se lisent
 * par `get_sub_field()` et non par `get_field()`, qui remonterait au niveau de
 * la page et ne trouverait rien.
 *
 * @param string $selector Nom ou clé du sous-champ.
 */
function lcds_sub_field(string $selector): mixed
{
    return function_exists('get_sub_field') ? get_sub_field($selector) : null;
}

/**
 * Valeur d'un sous-champ, ramenée à une chaîne propre.
 *
 * @param string $selector Nom ou clé du sous-champ.
 */
function lcds_sub_field_text(string $selector): string
{
    $value = lcds_sub_field($selector);

    return is_scalar($value) ? trim((string) $value) : '';
}

/**
 * Identifiant d'attachement d'un champ image, quel que soit son format de retour.
 *
 * ACF rend un entier, un tableau ou une URL selon le réglage du champ. On ne
 * garde que l'identifiant : c'est la seule forme que lcds_render_image() sait
 * servir en WebP — voir readme/images.md.
 *
 * @param mixed $value Valeur brute d'un champ image.
 */
function lcds_attachment_id(mixed $value): int
{
    if (is_numeric($value)) {
        return (int) $value;
    }

    if (is_array($value) && isset($value['ID'])) {
        return (int) $value['ID'];
    }

    return 0;
}

/**
 * @param mixed $value Raw value of an ACF link field (array return format).
 *
 * @return array{label: string, url: string, new_tab: bool}
 */
function lcds_link(mixed $value): array
{
    $link = is_array($value) ? $value : [];

    return [
        'label' => trim((string) ($link['title'] ?? '')),
        'url' => trim((string) ($link['url'] ?? '')),
        'new_tab' => ($link['target'] ?? '') === '_blank',
    ];
}

/**
 * Éléments d'un rail, depuis les lignes d'un répéteur de galerie.
 *
 * Le contributeur choisit une FORME NOMMÉE ; c'est l'enum qui en donne la
 * largeur. Partagé par la galerie de l'accueil et celle des conseils, qui
 * déclarent les mêmes sous-champs : la conversion recopiée aurait divergé dès
 * la première forme ajoutée.
 *
 * @param mixed $rows Lignes du répéteur, telles qu'ACF les rend.
 */
function lcds_gallery_items(mixed $rows): array
{
    $items = [];

    // `is_array` et non `?? []` : ACF rend `false` pour un répéteur sans ligne,
    // et `(array) false` vaut `[false]` — une ligne fantôme.
    foreach ((is_array($rows) ? $rows : []) as $row) {
        if (! is_array($row)) {
            continue;
        }

        $shape = LcdsMediaShape::fromValue($row['forme'] ?? null, LcdsMediaShape::Medium);
        $images = [lcds_attachment_id($row['image'] ?? null)];

        if ($shape->isPair()) {
            $images[] = lcds_attachment_id($row['image_2'] ?? null);
        }

        $items[] = ['width' => $shape->width(), 'images' => $images];
    }

    return $items;
}

/**
 * Alimente les listes de formes depuis LcdsMediaShape.
 *
 * Les choix ne sont PAS écrits dans le JSON du groupe : ils vivraient alors à
 * deux endroits, et une largeur ajoutée à l'enum n'apparaîtrait pas dans
 * l'administration. Le JSON ne déclare que le champ, l'enum en fournit le
 * contenu — voir inc/enums/LcdsMediaShape.php.
 *
 * @param array $field Définition du champ, telle qu'ACF la charge.
 */
function lcds_load_gallery_shapes(array $field): array
{
    $field['choices'] = LcdsMediaShape::choices([
        LcdsMediaShape::Large,
        LcdsMediaShape::Pair,
        LcdsMediaShape::Medium,
        LcdsMediaShape::Small,
    ]);

    return $field;
}
// Accroché sur la CLÉ : une galerie de plus = une ligne de plus ici, sinon sa
// liste s'affiche vide.
add_filter('acf/load_field/key=field_lcds_histoire_forme', 'lcds_load_gallery_shapes');
add_filter('acf/load_field/key=field_lcds_conseils_gal_forme', 'lcds_load_gallery_shapes');

/**
 * Alimente les listes de couleurs de puce depuis LcdsDotColor.
 *
 * Accroché sur le NOM du champ et non sur sa clé : les trois blocs à étiquette
 * ont chacun leur propre clé, et une liste de couleurs recopiée trois fois est
 * exactement ce qu'on vient de supprimer.
 *
 * @param array $field Définition du champ, telle qu'ACF la charge.
 */
function lcds_load_dot_colors(array $field): array
{
    $field['choices'] = LcdsDotColor::choices();

    return $field;
}
add_filter('acf/load_field/name=puce', 'lcds_load_dot_colors');

/**
 * Alimente les listes d'icônes d'information depuis LcdsInfoIcon.
 *
 * Accroché sur le NOM et non sur la clé, comme les couleurs de puce : la même
 * liste sert les informations pratiques de l'accueil et le « nous trouver » du
 * cabinet, et la prochaine page qui la reprendra n'aura rien à brancher.
 *
 * @param array $field Définition du champ, telle qu'ACF la charge.
 */
function lcds_load_info_icons(array $field): array
{
    $field['choices'] = LcdsInfoIcon::choices();

    return $field;
}
add_filter('acf/load_field/name=icone', 'lcds_load_info_icons');

/**
 * Alimente la couleur de puce de la page courante depuis LcdsDotColor.
 *
 * Accroché sur la CLÉ et non sur le nom : le champ vit sur la page de réglages
 * et n'a rien à voir avec les puces d'étiquette, qui sont accrochées sur le nom
 * `puce`. Les deux tirent leurs choix de la même enum — d'où « Vert » et
 * « Rouge » ici aussi, les noms du client.
 *
 * @param array $field Définition du champ, telle qu'ACF la charge.
 */
function lcds_load_nav_dot_colors(array $field): array
{
    $field['choices'] = LcdsDotColor::choices();

    return $field;
}
add_filter('acf/load_field/key=field_lcds_nav_puce', 'lcds_load_nav_dot_colors');

/**
 * Alimente la liste des cadrages depuis LcdsFocalPoint.
 *
 * @param array $field Définition du champ, telle qu'ACF la charge.
 */
function lcds_load_focal_points(array $field): array
{
    $field['choices'] = LcdsFocalPoint::choices();

    return $field;
}
add_filter('acf/load_field/key=field_lcds_footer_cadrage', 'lcds_load_focal_points');

/**
 * @param array $field Définition du champ, telle qu'ACF la charge.
 */
function lcds_load_video_sources(array $field): array
{
    $field['choices'] = LcdsVideoSource::choices();

    return $field;
}
add_filter('acf/load_field/key=field_lcds_hero_video_source', 'lcds_load_video_sources');

/**
 * @param bool|string $valid Verdict des validations précédentes.
 * @param mixed $value Valeur saisie.
 */
function lcds_validate_youtube_url(bool|string $valid, mixed $value): bool|string
{
    if ($valid !== true || ! is_string($value) || trim($value) === '') {
        return $valid;
    }

    return lcds_youtube_id($value) === ''
        ? __('Ce lien ne désigne pas une vidéo YouTube. Copiez l’adresse affichée sur la page de la vidéo.', 'lcds')
        : true;
}
add_filter('acf/validate_value/key=field_lcds_hero_youtube', 'lcds_validate_youtube_url', 10, 2);

/**
 * Migration 3.3.0 : recopie le premier visuel de chaque étape du parcours dans
 * le champ `visuel`, qui remplace le répéteur `visuels`.
 *
 * Vit dans le thème et non dans `bin/` : l'artefact de déploiement ne contient
 * pas `bin/`. Appelée par `bin/init.sh` et par le workflow de déploiement, via
 * WP-CLI. Idempotente : une étape déjà renseignée n'est pas touchée, et les
 * anciennes valeurs restent en base.
 *
 * @return int Nombre d'étapes migrées.
 */
function lcds_migrate_journey_visuals(): int
{
    global $wpdb;

    // Clés d'un répéteur dans un contenu flexible :
    // `<flexible>_<rang>_<répéteur>_<rang>_<sous-champ>`.
    $rows = $wpdb->get_results(
        "SELECT meta.post_id, meta.meta_key, meta.meta_value
           FROM {$wpdb->postmeta} AS meta
           JOIN {$wpdb->posts} AS post ON post.ID = meta.post_id
          WHERE post.post_type <> 'revision'
            AND meta.meta_key REGEXP '^sections_[0-9]+_etapes_[0-9]+_visuels_0_image$'",
    );
    $migrated = 0;

    foreach ((array) $rows as $row) {
        $postId = (int) $row->post_id;
        $target = (string) preg_replace('/_visuels_0_image$/', '_visuel', (string) $row->meta_key);
        $imageId = (int) $row->meta_value;

        if ($imageId === 0 || (int) get_post_meta($postId, $target, true) !== 0) {
            continue;
        }

        update_post_meta($postId, $target, $imageId);
        update_post_meta($postId, '_' . $target, 'field_lcds_parcours_visuel');
        $migrated++;
    }

    return $migrated;
}

/**
 * @param array $field Définition du champ, telle qu'ACF la charge.
 */
function lcds_load_week_days(array $field): array
{
    $field['choices'] = LcdsWeekDay::choices();

    return $field;
}
add_filter('acf/load_field/key=field_lcds_business_jours', 'lcds_load_week_days');
