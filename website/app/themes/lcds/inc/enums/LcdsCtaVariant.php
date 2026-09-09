<?php

/**
 * Variantes du bouton d'action — source unique de vérité.
 *
 * Le NOM du cas est celui de la maquette, sa VALEUR celle de la classe CSS.
 * Les deux vocabulaires divergent — Figma dit primary / secondary, la feuille
 * de style dit solid / outline — et c'est ici, et nulle part ailleurs, que la
 * correspondance vit. Renommer une classe CSS revient donc à changer une seule
 * ligne.
 *
 * CONSÉQUENCE À CONNAÎTRE : depuis que `className()` compose la classe, les
 * chaînes `cta--solid` et `cta--outline` n'apparaissent plus littéralement dans
 * aucun gabarit. Un `grep cta--outline` ne trouve donc que la feuille de style
 * et ce fichier — c'est ici qu'il faut regarder.
 *
 * Pas de `choices()` ni de `label()`, contrairement aux autres enums du thème :
 * AUCUN champ ACF n'offre cette variante au contributeur. Elle est imposée par
 * l'emplacement — le pied de page et les informations pratiques demandent la
 * secondaire, tout le reste prend le défaut. Les ajouter serait du code mort.
 *
 * Ajouter une variante = ajouter un cas, son bras dans `hasIcon()`, et la règle
 * correspondante dans assets/styles/components/cta.scss. Le `match` est sans
 * bras par défaut : un cas oublié lève UnhandledMatchError au lieu de rendre un
 * bouton sans glyphe silencieusement.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

enum LcdsCtaVariant: string
{
    // Deux pastilles, le glyphe puis le libellé, reliées par un collet.
    case Primary = 'solid';

    // Une seule pastille contournée, sans glyphe.
    case Secondary = 'outline';

    /**
     * Porte-t-elle la pastille de glyphe ?
     *
     * C'est la seule différence de BALISAGE entre les deux variantes : tout le
     * reste se joue dans la feuille de style.
     */
    public function hasIcon(): bool
    {
        return match ($this) {
            self::Primary => true,
            self::Secondary => false,
        };
    }

    /**
     * Classe CSS du composant.
     */
    public function className(): string
    {
        return 'cta cta--' . $this->value;
    }

    /**
     * Variante correspondant à une valeur reçue, ou un repli sûr.
     *
     * Un argument absent ou invalide ne doit pas casser le rendu.
     */
    public static function fromValue(mixed $value, self $fallback): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? $fallback) : $fallback;
    }
}
