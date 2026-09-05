<?php

/**
 * Rendu des menus du thème.
 *
 * Les tableaux d'arguments vivent ici et non dans les gabarits : Pint désaligne
 * un tableau multi-lignes noyé dans du balisage (`statement_indentation`).
 *
 * @package lcds
 */

require_once __DIR__ . '/enums/LcdsMenuLocation.php';

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Navigation principale de l'en-tête.
 *
 * `depth` vaut 1 : la maquette ne prévoit aucun déroulant. Un second niveau
 * ajouté en administration ne serait pas rendu.
 */
function lcds_header_nav(): void
{
    wp_nav_menu([
        'theme_location' => LcdsMenuLocation::Header->value,
        'container' => 'nav',
        'container_class' => 'site-nav',
        'container_aria_label' => __('Navigation principale', 'lcds'),
        'menu_class' => 'site-nav__list',
        'depth' => 1,
        'fallback_cb' => false,
    ]);
}

/**
 * Bouton d'action de l'en-tête.
 *
 * Emplacement distinct de la navigation : un contributeur ne peut donc pas le
 * glisser au milieu des liens, où sa mise en forme n'aurait aucun sens.
 */
function lcds_header_cta(): void
{
    wp_nav_menu([
        'theme_location' => LcdsMenuLocation::HeaderCta->value,
        // Chaîne vide et non `false` : même effet côté WordPress, et le type
        // déclaré par les stubs reste respecté.
        'container' => '',
        'menu_class' => 'site-header__cta',
        'depth' => 1,
        'fallback_cb' => false,
    ]);
}

/**
 * Navigation du pied de page.
 *
 * `depth` vaut 1 : la maquette ne prévoit aucun déroulant.
 */
function lcds_footer_nav(): void
{
    wp_nav_menu([
        'theme_location' => LcdsMenuLocation::Footer->value,
        'container' => 'nav',
        'container_class' => 'site-footer__nav',
        'container_aria_label' => __('Navigation du pied de page', 'lcds'),
        'menu_class' => 'site-footer__list',
        'depth' => 1,
        'fallback_cb' => false,
    ]);
}

/**
 * Liens légaux du pied de page.
 *
 * Emplacement distinct de la navigation : la maquette les sépare visuellement,
 * et un contributeur ne doit pas pouvoir glisser « Mentions légales » au milieu
 * des pages du site.
 */
function lcds_footer_legal(): void
{
    wp_nav_menu([
        'theme_location' => LcdsMenuLocation::Legal->value,
        'container' => 'nav',
        'container_class' => 'site-footer__legal',
        'container_aria_label' => __('Informations légales', 'lcds'),
        'menu_class' => 'site-footer__list',
        'depth' => 1,
        'fallback_cb' => false,
    ]);
}
