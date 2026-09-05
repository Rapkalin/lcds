<?php

/**
 * Cadrage d'un visuel recadré — source unique de vérité.
 *
 * Un visuel plein-cadre est rogné par `object-fit: cover` : il ne montre qu'une
 * bande de la photo. Laquelle relève du CONTENU, pas du gabarit — une bouche en
 * bas de cadre et un visage en haut ne se cadrent pas pareil. Le contributeur
 * choisit donc un nom, jamais une valeur CSS.
 *
 * La valeur du cas est le suffixe de la classe : `Top` donne `is-focus-top`.
 * L'`object-position` correspondante vit dans `partials/footer.scss` et non
 * ici, parce qu'elle n'est pas une simple constante — elle compense le débord
 * du visuel sous les coins arrondis du panneau, dont seule la feuille connaît
 * le rayon.
 *
 * Ajouter un cadrage = ajouter un cas, son bras dans `label()`, et la règle
 * correspondante dans `partials/footer.scss`. Le `match` est sans bras par
 * défaut : un cas oublié lève UnhandledMatchError. Une règle oubliée, elle, est
 * attrapée par la campagne de QA, qui exige des positions distinctes.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

enum LcdsFocalPoint: string
{
    case Top = 'top';
    case Center = 'center';
    case Bottom = 'bottom';

    /**
     * Libellé affiché dans l'administration.
     */
    public function label(): string
    {
        return match ($this) {
            self::Top => __('Haut de l’image', 'lcds'),
            self::Center => __('Centre', 'lcds'),
            self::Bottom => __('Bas de l’image', 'lcds'),
        };
    }

    /**
     * Cadrages au format attendu par un champ de sélection ACF.
     *
     * @return array<string, string>
     */
    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $point) {
            $choices[$point->value] = $point->label();
        }

        return $choices;
    }

    /**
     * Cadrage correspondant à une valeur enregistrée, ou un repli sûr.
     */
    public static function fromValue(mixed $value, self $fallback): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? $fallback) : $fallback;
    }
}
