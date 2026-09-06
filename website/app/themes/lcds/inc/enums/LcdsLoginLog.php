<?php

/**
 * Journal des connexions — bornes et mise en forme.
 *
 * Les deux méthodes sont PURES : l'instant leur est passé en argument plutôt
 * que lu à l'horloge, et rien n'y touche à la base. C'est ce qui rend la
 * rétention vérifiable par la suite de tests, là où l'écran ne l'est pas.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

final class LcdsLoginLog
{
    /**
     * Nombre d'entrées conservées.
     */
    public const MAX_ENTRIES = 200;

    /**
     * Ancienneté maximale d'une entrée, en jours.
     *
     * Une connexion est une donnée personnelle : le journal se borne dans les
     * DEUX sens, en âge et en nombre, et ce qui sort est supprimé, pas masqué.
     */
    public const MAX_AGE_DAYS = 90;

    /**
     * Ajoute une connexion en tête et ramène le journal dans ses bornes.
     */
    public static function record(array $entries, int $userId, string $login, int $now): array
    {
        array_unshift($entries, ['id' => $userId, 'login' => $login, 'time' => $now]);

        return self::prune($entries, $now);
    }

    /**
     * Écarte ce qui est trop vieux, trop nombreux, ou mal formé.
     *
     * Le tri n'est pas une coquetterie : sans lui, une entrée arrivée en retard
     * ferait tomber la plus récente au moment de couper à MAX_ENTRIES.
     */
    public static function prune(array $entries, int $now): array
    {
        $limite = $now - (self::MAX_AGE_DAYS * 86400);
        $gardees = [];

        foreach ($entries as $entree) {
            if (! self::isWellFormed($entree) || (int) $entree['time'] < $limite) {
                continue;
            }

            $gardees[] = [
                'id' => (int) $entree['id'],
                'login' => (string) $entree['login'],
                'time' => (int) $entree['time'],
            ];
        }

        usort($gardees, static fn(array $avant, array $apres): int => $apres['time'] <=> $avant['time']);

        return array_slice($gardees, 0, self::MAX_ENTRIES);
    }

    /**
     * Dernière connexion de chaque compte, par identifiant.
     *
     * S'appuie sur l'ordre du journal — du plus récent au plus ancien — donc la
     * PREMIÈRE occurrence d'un compte est sa dernière connexion. C'est pourquoi
     * l'entrée passe par `prune()`, qui trie, plutôt que d'être lue telle quelle.
     */
    public static function latestPerUser(array $entries): array
    {
        $derniers = [];

        foreach ($entries as $entree) {
            $derniers[$entree['id']] ??= $entree['time'];
        }

        return $derniers;
    }

    /**
     * Une option peut avoir été écrite par une version antérieure, ou à la main.
     */
    private static function isWellFormed(mixed $entree): bool
    {
        return is_array($entree)
            && isset($entree['id'], $entree['login'], $entree['time'])
            && is_numeric($entree['id'])
            && is_numeric($entree['time'])
            && is_scalar($entree['login']);
    }
}
