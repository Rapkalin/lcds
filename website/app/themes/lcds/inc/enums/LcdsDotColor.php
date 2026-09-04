<?php

/**
 * Couleurs de puce d'étiquette proposées aux contributeurs — source unique.
 *
 * ATTENTION : le libellé affiché et la valeur enregistrée ne coïncident PAS.
 * Le client nomme ces deux couleurs « Vert » et « Rouge », la bibliothèque
 * Figma et la feuille de style les nomment `turquoise` (#048B8C) et `orange`
 * (#E25304). Décision assumée : seul le libellé change, la valeur enregistrée
 * et la classe CSS restent celles du système de design.
 *
 * Conséquence à connaître : un contributeur qui dit avoir choisi « Rouge » a
 * produit `dot => 'orange'` et la classe `.tag--orange`. Sans cette enum, la
 * correspondance vivait en trois exemplaires dans `acf-json/` et personne ne
 * pouvait la retrouver.
 *
 * Ajouter une couleur = ajouter un cas, son bras dans `label()`, la variable
 * dans `basics/variables.scss` et sa règle dans `components/tag.scss`. Le
 * `match` est sans bras par défaut : un cas oublié lève UnhandledMatchError au
 * lieu de rendre une puce transparente.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

enum LcdsDotColor: string
{
    case Turquoise = 'turquoise';
    case Orange = 'orange';

    /**
     * Libellé affiché dans l'administration — le nom du client, pas celui du
     * système de design.
     */
    public function label(): string
    {
        return match ($this) {
            self::Turquoise => __('Vert', 'lcds'),
            self::Orange => __('Rouge', 'lcds'),
        };
    }

    /**
     * Couleurs au format attendu par un champ de sélection ACF.
     *
     * @return array<string, string>
     */
    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $color) {
            $choices[$color->value] = $color->label();
        }

        return $choices;
    }

    /**
     * Couleur correspondant à une valeur enregistrée, ou un repli sûr.
     *
     * Un champ vidé ou une valeur devenue invalide ne doit pas casser le rendu.
     */
    public static function fromValue(mixed $value, self $fallback): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? $fallback) : $fallback;
    }
}
