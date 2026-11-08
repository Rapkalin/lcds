<?php

/**
 * Jours d'ouverture de l'établissement.
 *
 * Les valeurs sont les noms de jour de schema.org (`dayOfWeek`) : elles partent
 * telles quelles dans les données structurées, et sont stockées en base.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

enum LcdsWeekDay: string
{
    case Monday = 'Monday';
    case Tuesday = 'Tuesday';
    case Wednesday = 'Wednesday';
    case Thursday = 'Thursday';
    case Friday = 'Friday';
    case Saturday = 'Saturday';
    case Sunday = 'Sunday';

    public function label(): string
    {
        return match ($this) {
            self::Monday => __('Lundi', 'lcds'),
            self::Tuesday => __('Mardi', 'lcds'),
            self::Wednesday => __('Mercredi', 'lcds'),
            self::Thursday => __('Jeudi', 'lcds'),
            self::Friday => __('Vendredi', 'lcds'),
            self::Saturday => __('Samedi', 'lcds'),
            self::Sunday => __('Dimanche', 'lcds'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $day) {
            $choices[$day->value] = $day->label();
        }

        return $choices;
    }
}
