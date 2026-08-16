<?php

/**
 * Configuration overrides for WP_ENV === 'production'
 *
 * config/application.php already carries the production defaults (debug off,
 * file edits and file mods disabled, errors never displayed). This file exists
 * so production-only settings have an obvious home; keep it as thin as possible.
 */

// Un appel à env() dans ce fichier exige d'y ajouter `use function Env\env;` :
// l'import de application.php ne vaut que pour son propre fichier. Sans lui,
// « Call to undefined function env() » fait tomber tout l'environnement.
