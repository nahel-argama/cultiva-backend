<?php

namespace Tests\Unit\domain\Integrations\ProductImage\Clients;

use Cultiva\Integrations\ProductImage\Clients\ProductImageClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductImageClientTest extends TestCase
{
    private ProductImageClient $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = app(ProductImageClient::class);
    }

    public function test_it_searches_duckduckgo_and_downloads_first_image_when_results_found(): void
    {
        Http::fake([
            'duckduckgo.com/?*' => Http::response('<html><script>vqd=51234567890-123&</script></html>', 200),
            'duckduckgo.com/i.js*' => Http::response([
                'results' => [
                    ['image' => 'https://images.example.com/tomato.jpg'],
                ],
            ], 200),
            'images.example.com/tomato.jpg' => Http::response('fake-jpg-binary-content', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $result = $this->sut->searchAndDownload('Tomate produto isolado fundo branco');

        $this->assertSame('fake-jpg-binary-content', $result);
    }

    public function test_it_falls_back_to_wikimedia_when_duckduckgo_returns_no_results(): void
    {
        Http::fake([
            'duckduckgo.com/?*' => Http::response('<html>no token</html>', 200),
            'commons.wikimedia.org/w/api.php*' => Http::response([
                'query' => [
                    'pages' => [
                        '123' => [
                            'imageinfo' => [
                                [
                                    'url' => 'https://upload.wikimedia.org/tomato_wikimedia.jpg',
                                    'mime' => 'image/jpeg',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
            'upload.wikimedia.org/tomato_wikimedia.jpg' => Http::response('wikimedia-binary-content', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $result = $this->sut->searchAndDownload('Tomate produto isolado fundo branco');

        $this->assertSame('wikimedia-binary-content', $result);
    }

    public function test_it_returns_null_when_all_searches_fail_or_return_empty(): void
    {
        Http::fake([
            'duckduckgo.com/?*' => Http::response('<html>no token</html>', 200),
            'commons.wikimedia.org/w/api.php*' => Http::response([
                'query' => [
                    'pages' => [],
                ],
            ], 200),
        ]);

        $result = $this->sut->searchAndDownload('Nonexistent Produce');

        $this->assertNull($result);
    }
}
