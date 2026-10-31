<?php

namespace Cultiva\Integrations\ProductImage\Clients;

use Cultiva\Integrations\ProductImage\DTO\DownloadedImageDTO;
use finfo;
use Illuminate\Http\Client\Factory as HttpFactory;
use Throwable;

class ProductImageClient
{
    private const USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0';

    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    public function searchAndDownload(string $query): ?DownloadedImageDTO
    {
        $imageUrl = $this->searchDuckDuckGo($query) ?? $this->searchWikimedia($query);

        if ($imageUrl === null) {
            return null;
        }

        return $this->download($imageUrl);
    }

    private function searchDuckDuckGo(string $query): ?string
    {
        try {
            $tokenResponse = $this->http->withHeaders([
                'User-Agent' => self::USER_AGENT,
            ])->timeout(5)->get('https://duckduckgo.com/?q=' . urlencode($query));

            if (! $tokenResponse->successful()) {
                return null;
            }

            if (! preg_match('/vqd=([\d-]+)&/', $tokenResponse->body(), $matches)) {
                return null;
            }

            $vqd = $matches[1];

            $apiResponse = $this->http->withHeaders([
                'User-Agent' => self::USER_AGENT,
            ])->timeout(5)->get('https://duckduckgo.com/i.js', [
                'l' => 'wt-wt',
                'o' => 'json',
                'q' => $query,
                'vqd' => $vqd,
                'f' => ',,,',
                'p' => 1,
            ]);

            if (! $apiResponse->successful()) {
                return null;
            }

            $results = $apiResponse->json('results');

            if (! is_array($results) || empty($results)) {
                return null;
            }

            return $results[0]['image'] ?? null;
        } catch (Throwable) {
            return null;
        }
    }

    private function searchWikimedia(string $query): ?string
    {
        try {
            $response = $this->http->withHeaders([
                'User-Agent' => self::USER_AGENT,
            ])->timeout(5)->get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'generator' => 'search',
                'gsrsearch' => $query,
                'gsrnamespace' => 6,
                'prop' => 'imageinfo',
                'iiprop' => 'url|mime',
                'format' => 'json',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $pages = $response->json('query.pages');

            if (! is_array($pages) || empty($pages)) {
                return null;
            }

            foreach ($pages as $page) {
                $imageInfo = $page['imageinfo'][0] ?? null;
                if (! is_array($imageInfo)) {
                    continue;
                }

                $mime = $imageInfo['mime'] ?? '';
                if (str_starts_with($mime, 'image/')) {
                    return $imageInfo['url'] ?? null;
                }
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    private function download(string $url): ?DownloadedImageDTO
    {
        try {
            $response = $this->http->withHeaders([
                'User-Agent' => self::USER_AGENT,
            ])->timeout(10)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->body();
            $extension = $this->determineExtension($response->header('Content-Type'), $url, $body);

            return new DownloadedImageDTO(
                content: $body,
                extension: $extension,
            );
        } catch (Throwable) {
            return null;
        }
    }

    private function determineExtension(?string $contentType, string $url, string $body): string
    {
        if ($contentType !== null) {
            $normalizedContentType = strtolower(trim(explode(';', $contentType)[0]));
            $ext = match ($normalizedContentType) {
                'image/jpeg', 'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                default => null,
            };

            if ($ext !== null) {
                return $ext;
            }
        }

        $urlPath = parse_url($url, PHP_URL_PATH);
        $urlExt = strtolower(pathinfo($urlPath ?? '', PATHINFO_EXTENSION));
        if (in_array($urlExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return $urlExt === 'jpeg' ? 'jpg' : $urlExt;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($body);

        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }
}
