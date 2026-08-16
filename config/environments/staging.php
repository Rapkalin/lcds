<?php

/**
 * Configuration overrides for WP_ENV === 'staging'
 */

use Roots\WPConfig\Config;

/**
 * Staging should stay as close to production as possible; only the differences
 * that make the environment usable for review belong here.
 *
 * DISALLOW_INDEXING is provided by roots/bedrock-disallow-indexing and keeps
 * search engines away from the preprod copy of the site.
 */

// Un appel à env() dans ce fichier exige d'y ajouter `use function Env\env;` :
// l'import de application.php ne vaut que pour son propre fichier. Sans lui,
// « Call to undefined function env() » fait tomber tout l'environnement.
Config::define('DISALLOW_INDEXING', true);
Config::define('WP_DEBUG', true);
Config::define('WP_DEBUG_LOG', true);
