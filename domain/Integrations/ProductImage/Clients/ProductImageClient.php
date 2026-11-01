<?php

namespace Cultiva\Integrations\ProductImage\Clients;

use Illuminate\Http\Client\Factory as HttpFactory;
use Throwable;

class ProductImageClient
{
    private const USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0';

    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    public function searchAndDownload(string $query): ?string
    {
        $imageUrl = $this->searchDuckDuckGo($query)
            ?? $this->searchBing($query)
            ?? $this->searchWikimedia($query);

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

    private function searchBing(string $query): ?string
    {
        try {
            $response = $this->http->withHeaders([
                'User-Agent' => self::USER_AGENT,
            ])->timeout(5)->get('https://www.bing.com/images/search', [
                'q' => $query,
                'qft' => '+filterui:photo-photo',
                'form' => 'IRFLTR',
                'first' => 1,
            ]);

            if (! $response->successful()) {
                return null;
            }

            if (! preg_match_all('/murl&quot;:&quot;(https?:\/\/[^&]+)&quot;/', $response->body(), $matches)) {
                return null;
            }

            foreach ($matches[1] as $url) {
                $decodedUrl = html_entity_decode($url, ENT_QUOTES | ENT_HTML5);
                if (str_contains($decodedUrl, 'lookaside.fbsbx.com') || str_contains($decodedUrl, 'lookaside.instagram.com')) {
                    continue;
                }

                return $decodedUrl;
            }

            return null;
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

    private function download(string $url): ?string
    {
        try {
            $response = $this->http->withHeaders([
                'User-Agent' => self::USER_AGENT,
            ])->timeout(10)->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }
}
