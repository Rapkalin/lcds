<?php

/**
 * Icônes des entrées d'informations pratiques — source unique de vérité.
 *
 * La valeur du cas est le suffixe du composant : `Pin` charge
 * `components/icon-pin.php`. Le nom de fichier n'est donc jamais écrit dans un
 * gabarit, et une icône introuvable est impossible tant que le cas existe.
 *
 * Les tracés sont des REDESSINS d'après la maquette, qui référence un jeu
 * d'icônes tiers (`location-target-2--…`, `school-bus-side`, …) dont le projet
 * n'a pas les fichiers. À remplacer par les assets du designer quand ils
 * arrivent — voir readme/accessibilite.md pour la contrainte de contraste.
 *
 * Ajouter une icône = ajouter un cas, son bras dans `label()` et le composant
 * `components/icon-<valeur>.php`. Le `match` est sans bras par défaut : un cas
 * oublié lève UnhandledMatchError au lieu de rendre une entrée sans glyphe.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

enum LcdsInfoIcon: string
{
    case Pin = 'pin';
    case Bus = 'bus';
    case Info = 'info';
    case Clock = 'clock';
    case User = 'user';

    /**
     * Libellé affiché dans l'administration.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pin => __('Adresse', 'lcds'),
            self::Bus => __('Transports', 'lcds'),
            self::Info => __('Information', 'lcds'),
            self::Clock => __('Horaires', 'lcds'),
            self::User => __('Contact', 'lcds'),
        };
    }

    /**
     * Chemin du composant de glyphe, relatif au thème.
     */
    public function template(): string
    {
        return 'components/icon-' . $this->value;
    }

    /**
     * Icônes au format attendu par un champ de sélection ACF.
     *
     * @return array<string, string>
     */
    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $icon) {
            $choices[$icon->value] = $icon->label();
        }

        return $choices;
    }

    /**
     * Icône correspondant à une valeur enregistrée, ou un repli sûr.
     */
    public static function fromValue(mixed $value, self $fallback): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? $fallback) : $fallback;
    }
}
