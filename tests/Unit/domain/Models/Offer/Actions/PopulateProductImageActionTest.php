<?php

namespace Tests\Unit\domain\Models\Offer\Actions;

use Cultiva\Integrations\ProductImage\Clients\ProductImageClient;
use Cultiva\Integrations\ProductSource\Actions\GetProductAction;
use Cultiva\Integrations\ProductSource\Clients\ProductSourceClient;
use Cultiva\Models\Offer\Actions\PopulateProductImageAction;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PopulateProductImageActionTest extends TestCase
{
    private ProductImageClient $clientMock;
    private ProductSourceClient $productSourceClientMock;
    private GetProductAction $getProduct;
    private PopulateProductImageAction $sut;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->clientMock = Mockery::mock(ProductImageClient::class);
        $this->productSourceClientMock = Mockery::mock(ProductSourceClient::class);
        $this->getProduct = new GetProductAction($this->productSourceClientMock);

        $this->sut = new PopulateProductImageAction(
            $this->clientMock,
            $this->getProduct,
        );
    }

    public function test_it_skips_download_if_image_already_exists_in_storage(): void
    {
        Storage::disk('public')->put('products/123.webp', 'pre-existing-webp');

        $this->clientMock->shouldNotReceive('searchAndDownload');
        $this->productSourceClientMock->shouldNotReceive('getProduct');

        $result = $this->sut->execute('123', 'Tomate');

        $this->assertSame('products/123.webp', $result);
        $this->assertSame('pre-existing-webp', Storage::disk('public')->get('products/123.webp'));
    }

    public function test_it_searches_downloads_converts_to_webp_and_stores_image(): void
    {
        $gd = imagecreatetruecolor(10, 10);
        ob_start();
        imagejpeg($gd);
        $jpegBytes = ob_get_clean();
        imagedestroy($gd);

        $this->clientMock->shouldReceive('searchAndDownload')
            ->once()
            ->with('Tomate produto isolado fundo branco')
            ->andReturn($jpegBytes);

        $result = $this->sut->execute('123', 'Tomate');

        $this->assertSame('products/123.webp', $result);
        $this->assertTrue(Storage::disk('public')->exists('products/123.webp'));

        $storedBytes = Storage::disk('public')->get('products/123.webp');
        $loadedImage = imagecreatefromstring($storedBytes);
        $this->assertNotFalse($loadedImage);
        imagedestroy($loadedImage);
    }

    public function test_it_fetches_product_name_from_get_product_action_if_not_provided(): void
    {
        $gd = imagecreatetruecolor(10, 10);
        ob_start();
        imagejpeg($gd);
        $jpegBytes = ob_get_clean();
        imagedestroy($gd);

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('status')->andReturn(200);
        $mockResponse->shouldReceive('successful')->andReturn(true);
        $mockResponse->shouldReceive('json')->andReturn([
            'id' => '456',
            'name' => 'Banana',
        ]);

        $this->productSourceClientMock->shouldReceive('getProduct')
            ->once()
            ->with('456')
            ->andReturn($mockResponse);

        $this->clientMock->shouldReceive('searchAndDownload')
            ->once()
            ->with('Banana produto isolado fundo branco')
            ->andReturn($jpegBytes);

        $result = $this->sut->execute('456');

        $this->assertSame('products/456.webp', $result);
        $this->assertTrue(Storage::disk('public')->exists('products/456.webp'));
    }

    public function test_it_logs_warning_and_stores_placeholder_when_download_fails(): void
    {
        Log::spy();

        $this->clientMock->shouldReceive('searchAndDownload')
            ->once()
            ->with('Abacaxi produto isolado fundo branco')
            ->andReturn(null);

        $result = $this->sut->execute('789', 'Abacaxi');

        $this->assertSame('products/789.webp', $result);
        $this->assertTrue(Storage::disk('public')->exists('products/789.webp'));

        Log::shouldHaveReceived('warning')
            ->once()
            ->with(Mockery::pattern('/Failed to populate product image/'));
    }
}
