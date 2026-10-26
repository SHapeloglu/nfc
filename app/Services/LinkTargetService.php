<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

/**
 * Validates supported NFC destinations and extracts their public metadata.
 */
class LinkTargetService
{
    /**
     * @return array<string, string>
     */
    public function metadataFor(string $type, string $targetUrl): array
    {
        $url = parse_url($targetUrl);

        if (! is_array($url)) {
            $this->reject('Geçersiz bağlantı.');
        }

        $scheme = strtolower($url['scheme'] ?? '');
        $host = strtolower($url['host'] ?? '');

        if (
            $scheme !== 'https' ||
            isset($url['user']) ||
            isset($url['pass']) ||
            isset($url['port']) ||
            isset($url['fragment'])
        ) {
            $this->reject('Geçerli bir HTTPS bağlantısı girin.');
        }

        parse_str($url['query'] ?? '', $params);

        if ($type === 'google_review') {
            $this->requireSingleQueryParameter($url['query'] ?? '', 'placeid');

            $placeId = $params['placeid'] ?? null;

            if (
                $host !== 'search.google.com' ||
                ($url['path'] ?? '') !== '/local/writereview' ||
                ! is_string($placeId) ||
                ! preg_match('/^[A-Za-z0-9_-]{10,200}$/', $placeId)
            ) {
                $this->reject('Geçerli Google yorum bağlantısı girin.');
            }

            return ['place_id' => $placeId];
        }

        if ($type === 'instagram_follow') {
            $this->requireSingleQueryParameter($url['query'] ?? '', 'xtok');

            $username = trim($url['path'] ?? '', '/');
            $token = $params['xtok'] ?? null;

            if (
                ! in_array($host, ['instagram.com', 'www.instagram.com'], true) ||
                ! preg_match('/^[A-Za-z0-9._]{1,30}$/', $username) ||
                ! is_string($token) ||
                ! preg_match('/^[A-Za-z0-9_+\/=-]{8,256}$/', $token)
            ) {
                $this->reject('Geçerli Instagram paylaşım bağlantısı girin.');
            }

            return ['username' => $username];
        }

        $this->reject('Desteklenmeyen bağlantı türü.');
    }

    private function requireSingleQueryParameter(string $query, string $parameter): void
    {
        $count = 0;

        foreach (explode('&', $query) as $part) {
            $name = rawurldecode(explode('=', $part, 2)[0]);

            if ($name === $parameter) {
                $count++;
            }
        }

        if ($count !== 1) {
            $this->reject('Bağlantı parametresi tam olarak bir kez belirtilmelidir.');
        }
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['target_url' => $message]);
    }
}
