<?php

namespace App\Support;

/**
 * Convierte enlaces de YouTube y Vimeo en URLs de inserción seguras.
 */
class VideoEmbed
{
    /**
     * @return string|null URL de inserción, o null si el enlace no es de YouTube/Vimeo.
     */
    public static function normalize(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower(preg_replace('/^(www\.|m\.)/', '', $parts['host'] ?? ''));
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        $youtubeId = match (true) {
            $host === 'youtu.be' => trim($path, '/'),
            in_array($host, ['youtube.com', 'youtube-nocookie.com', 'music.youtube.com'], true) && $path === '/watch' => $query['v'] ?? null,
            in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)
                && preg_match('#^/(embed|shorts|live)/([^/?]+)#', $path, $m) === 1 => $m[2],
            default => null,
        };

        if ($youtubeId !== null) {
            return preg_match('/^[A-Za-z0-9_-]{11}$/', $youtubeId)
                ? "https://www.youtube-nocookie.com/embed/{$youtubeId}"
                : null;
        }

        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true)
            && preg_match('#^/(?:video/)?(\d{6,12})(?:/([A-Za-z0-9]+))?#', $path, $m) === 1) {
            // Los videos no listados de Vimeo incluyen un hash privado después del ID.
            $hash = $m[2] ?? ($query['h'] ?? null);

            return "https://player.vimeo.com/video/{$m[1]}".($hash ? '?h='.urlencode($hash) : '');
        }

        return null;
    }
}
