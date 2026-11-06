<?php

/**
 * Provenance de la vidéo de fond du hero.
 *
 * Ajouter une provenance = ajouter un cas, son bras dans `label()`, et son
 * rendu dans components/hero.php. Les valeurs sont aussi citées par la logique
 * conditionnelle du groupe de champs de l'accueil : les renommer casse
 * l'affichage des champs dans l'administration.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

enum LcdsVideoSource: string
{
    case File = 'file';
    case Youtube = 'youtube';

    public function label(): string
    {
        return match ($this) {
            self::File => __('Fichier de la médiathèque', 'lcds'),
            self::Youtube => __('Vidéo YouTube', 'lcds'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $source) {
            $choices[$source->value] = $source->label();
        }

        return $choices;
    }

    public static function fromValue(mixed $value, self $fallback): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? $fallback) : $fallback;
    }
}
