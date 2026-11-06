<?php

/**
 * Lecture des liens YouTube saisis par les contributeurs.
 *
 * Seul l'identifiant de la vidéo sort d'ici : l'URL d'intégration est
 * reconstruite par le thème, sur le domaine youtube-nocookie.com. L'URL saisie
 * n'est donc jamais réécrite telle quelle dans la page.
 *
 * Fichier sans hook : la suite de tests le charge hors WordPress.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

const LCDS_YOUTUBE_HOSTS = ['youtube.com', 'youtu.be', 'youtube-nocookie.com'];

const LCDS_YOUTUBE_PATH_PREFIXES = ['embed', 'shorts', 'live', 'v'];

/**
 * Identifiant d'une vidéo YouTube, ou une chaîne vide si le lien n'en désigne pas.
 */
function lcds_youtube_id(string $url): string
{
    $parts = parse_url(trim($url));

    if (! is_array($parts) || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
        return '';
    }

    $host = (string) preg_replace('/^(www|m)\./', '', strtolower($parts['host'] ?? ''));

    if (! in_array($host, LCDS_YOUTUBE_HOSTS, true)) {
        return '';
    }

    $segments = array_values(array_filter(explode('/', $parts['path'] ?? '')));

    if ($host === 'youtu.be') {
        $candidate = $segments[0] ?? '';
    } elseif (($segments[0] ?? '') === 'watch') {
        parse_str($parts['query'] ?? '', $query);
        $candidate = is_string($query['v'] ?? null) ? $query['v'] : '';
    } elseif (in_array($segments[0] ?? '', LCDS_YOUTUBE_PATH_PREFIXES, true)) {
        $candidate = $segments[1] ?? '';
    } else {
        $candidate = '';
    }

    return preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) === 1 ? $candidate : '';
}
