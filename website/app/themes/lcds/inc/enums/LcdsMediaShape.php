<?php

/**
 * Formes de cadre proposées aux contributeurs — source unique de vérité.
 *
 * Les largeurs du rail et des visuels d'étape sont un CHOIX GRAPHIQUE de la
 * maquette, pas une propriété des photos. Demander un nombre à un contributeur
 * serait absurde : il choisit une forme nommée, et la largeur vit ici.
 *
 * Ajouter une forme = ajouter un cas et son bras dans `width()` et `label()`.
 * Les `match` sont sans bras par défaut : un cas oublié lève
 * UnhandledMatchError au lieu de rendre un cadre de largeur nulle.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

enum LcdsMediaShape: string
{
    // Rail de la section « histoire » et carrousels.
    case Large = 'large';
    case Pair = 'pair';
    case Medium = 'medium';
    case Small = 'small';

    // Visuels accompagnant une étape du parcours de soin.
    case StepWide = 'step-wide';
    case StepNarrow = 'step-narrow';

    /**
     * Largeur du cadre en pixels, telle que dessinée sur la maquette.
     */
    public function width(): float
    {
        return match ($this) {
            self::Large => 892.0,
            self::Pair => 553.0,
            self::Medium => 666.0,
            self::Small => 503.2,
            self::StepWide => 327.0,
            self::StepNarrow => 214.0,
        };
    }

    /**
     * Libellé affiché dans l'administration.
     */
    public function label(): string
    {
        return match ($this) {
            self::Large => __('Grand (892 px)', 'lcds'),
            self::Pair => __('Deux visuels empilés (553 px)', 'lcds'),
            self::Medium => __('Moyen (666 px)', 'lcds'),
            self::Small => __('Petit (503 px)', 'lcds'),
            self::StepWide => __('Large (327 px)', 'lcds'),
            self::StepNarrow => __('Étroit (214 px)', 'lcds'),
        };
    }

    /**
     * Deux visuels au lieu d'un : seule la forme empilée en attend deux.
     */
    public function isPair(): bool
    {
        return $this === self::Pair;
    }

    /**
     * Formes au format attendu par un champ de sélection ACF.
     *
     * @param array $shapes Cas à proposer, dans l'ordre d'affichage.
     * @return array<string, string>
     */
    public static function choices(array $shapes): array
    {
        $choices = [];

        foreach ($shapes as $shape) {
            $choices[$shape->value] = $shape->label();
        }

        return $choices;
    }

    /**
     * Forme correspondant à une valeur enregistrée, ou un repli sûr.
     *
     * Un champ vidé ou une valeur devenue invalide ne doit pas casser le rendu.
     */
    public static function fromValue(mixed $value, self $fallback): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? $fallback) : $fallback;
    }
}
