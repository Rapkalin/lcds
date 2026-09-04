<?php

/**
 * Outils de gabarit.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Rend un composant et retourne son balisage au lieu de l'afficher.
 *
 * `get_template_part()` écrit sur la sortie : il n'y a pas d'option pour
 * récupérer le résultat. Or le rail du carrousel a besoin du balisage de ses
 * cartes SOUS FORME DE CHAÎNE, pour rester un composant unique servant les deux
 * carrousels de la maquette.
 *
 * @param string $part Chemin du composant, sans extension.
 * @param array  $args Arguments passés au composant.
 */
function lcds_capture(string $part, array $args = []): string
{
    ob_start();
    get_template_part($part, null, $args);

    return (string) ob_get_clean();
}

/**
 * Version du site, lue dans le `composer.json` de la racine.
 *
 * Source unique : la version est déjà déclarée là pour Composer, la dupliquer
 * dans le thème garantirait qu'elles divergent. L'en-tête `Version:` de
 * `style.css` reste à part — WordPress le parse comme du texte, il ne peut pas
 * contenir de PHP.
 *
 * `composer.json` vit HORS du docroot : il n'est donc pas servi par Apache, et
 * l'artefact de déploiement l'embarque explicitement — sans quoi la version
 * serait vide en production.
 *
 * Lue une fois par requête (`static`) et non mise en cache durablement : le
 * fichier pèse 2 Ko, alors qu'un cache persistant demanderait une invalidation
 * à chaque déploiement — précisément le moment où la valeur change.
 *
 * @return string La version, ou une chaîne vide si elle est introuvable.
 */
function lcds_site_version(): string
{
    static $version = null;

    if ($version !== null) {
        return $version;
    }

    $version = '';
    $manifest = dirname(ABSPATH, 2) . '/composer.json';

    if (! is_readable($manifest)) {
        return $version;
    }

    $decoded = json_decode((string) file_get_contents($manifest), true);

    if (is_array($decoded) && isset($decoded['version']) && is_string($decoded['version'])) {
        $version = trim($decoded['version']);
    }

    return $version;
}
