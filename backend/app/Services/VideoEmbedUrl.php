<?php

namespace App\Services;

class VideoEmbedUrl
{
    public function from(string $url, int $startAt = 0): string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'youtu.be'], true)) {
            $id = $host === 'youtu.be' ? trim($parts['path'] ?? '', '/') : $this->youtubeId($parts);

            return $id ? "https://www.youtube-nocookie.com/embed/{$id}?start={$startAt}&enablejsapi=1" : $url;
        }

        if (in_array($host, ['drive.google.com', 'www.drive.google.com'], true)
            && preg_match('~/file/d/([^/]+)~', $parts['path'] ?? '', $matches)) {
            return "https://drive.google.com/file/d/{$matches[1]}/preview";
        }

        return $url;
    }

    private function youtubeId(array $parts): ?string
    {
        parse_str($parts['query'] ?? '', $query);
        if (! empty($query['v'])) {
            return preg_replace('/[^a-zA-Z0-9_-]/', '', $query['v']);
        }
        if (preg_match('~/(?:embed|shorts)/([^/?]+)~', $parts['path'] ?? '', $matches)) {
            return $matches[1];
        }

        return null;
    }
}
