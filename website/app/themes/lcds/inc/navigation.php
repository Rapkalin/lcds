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
require_once __DIR__ . '/enums/LcdsDotColor.php';

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Teinte de la puce de page courante quand aucun réglage n'est enregistré.
 *
 * Source unique du repli, parce qu'il vit en deux autres exemplaires : le
 * `default_value` du champ ACF et le repli de `var(--nav-dot, …)`. Les trois
 * doivent donner la MÊME couleur, sinon la puce change de teinte au premier
 * enregistrement sans que personne ait rien choisi. La recette le verrouille.
 */
function lcds_nav_dot_fallback(): LcdsDotColor
{
    return LcdsDotColor::Orange;
}

/**
 * Navigation principale de l'en-tête.
 *
 * `depth` vaut 1 : la maquette ne prévoit aucun déroulant. Un second niveau
 * ajouté en administration ne serait pas rendu.
 */
function lcds_header_nav(): void
{
    // La couleur de la puce de la page courante vient des réglages du site :
    // c'est un choix de contribution, pas une constante de thème.
    $dot = LcdsDotColor::fromValue(lcds_option('puce_page_courante'), lcds_nav_dot_fallback());

    wp_nav_menu([
        'theme_location' => LcdsMenuLocation::Header->value,
        'container' => 'nav',
        'container_class' => 'site-nav site-nav--dot-' . $dot->value,
        'container_aria_label' => __('Navigation principale', 'lcds'),
        'menu_class' => 'site-nav__list',
        // La forme blanche de la barre, peinte derrière les liens par
        // `initNavShape`. Elle est posée ici plutôt que par le script pour que
        // le navigateur n'ait pas à refaire la mise en page en la découvrant.
        // Vide et décorative : sans JavaScript elle ne peint rien, et ce sont
        // les fonds des pastilles qui rendent les liens lisibles.
        'items_wrap' => '<svg class="site-nav__shape" aria-hidden="true" focusable="false">'
            . '<path d="" /></svg>'
            . '<ul id="%1$s" class="%2$s">%3$s</ul>',
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
