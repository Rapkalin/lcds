<?php

/**
 * Correctifs liés à Yoast.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Remet en français les gabarits de titre de Yoast restés en anglais.
 *
 * Yoast écrit ses gabarits par défaut dans l'option `wpseo_titles` au moment de
 * son ACTIVATION. Activé avant l'installation de son paquet de langue, il y
 * range les chaînes anglaises — et installer la traduction ensuite ne réécrit
 * rien. Constaté : « Page not found - La Clinique du Sourire » et « You
 * searched for … » sur un site déclaré en `fr`, plus quatre libellés de fil
 * d'Ariane.
 *
 * NON DESTRUCTIF, et ce n'est pas une promesse en l'air : la locale est
 * basculée en `en_US` pour demander à Yoast ses défauts anglais, et seules les
 * clés dont la valeur enregistrée est exactement ce défaut anglais sont
 * retirées. Un gabarit saisi par un contributeur ne peut donc pas être touché.
 * Une clé retirée est recalculée par Yoast depuis ses défauts, cette fois
 * traduits.
 *
 * Vit dans le thème et non dans `bin/` : l'artefact de déploiement ne contient
 * que `website/`, `config/` et `wp-cli.yml` — un script de `bin/` serait
 * introuvable sur le serveur. Appelée par `bin/init.sh` et par le workflow de
 * déploiement, tous deux via WP-CLI. Idempotente.
 *
 * @return array Clés remises au défaut, vide s'il n'y avait rien à corriger.
 */
function lcds_reset_seo_titles(): array
{
    if (! class_exists('WPSEO_Options')) {
        return [];
    }

    $stored = get_option('wpseo_titles');

    if (! is_array($stored)) {
        return [];
    }

    // Les défauts anglais sont demandés à Yoast plutôt qu'écrits en dur : la
    // liste des gabarits change d'une version du plugin à l'autre.
    switch_to_locale('en_US');
    $english = [];

    foreach (array_keys($stored) as $key) {
        $english[(string) $key] = WPSEO_Options::get_default('wpseo_titles', (string) $key);
    }

    restore_previous_locale();

    $removed = [];

    foreach ($english as $key => $default) {
        if (! is_string($default) || $default === '') {
            continue;
        }

        if (($stored[$key] ?? null) !== $default) {
            continue;
        }

        // Défaut identique dans les deux langues : rien à corriger, et le
        // retirer serait un changement gratuit.
        if (WPSEO_Options::get_default('wpseo_titles', $key) === $default) {
            continue;
        }

        unset($stored[$key]);
        $removed[] = $key;
    }

    if ($removed !== []) {
        update_option('wpseo_titles', $stored);
    }

    return $removed;
}
