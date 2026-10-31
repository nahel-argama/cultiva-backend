<?php

namespace Tests\Unit\domain\Models\Offer\Actions;

use Cultiva\Integrations\ProductImage\Clients\ProductImageClient;
use Cultiva\Integrations\ProductImage\DTO\DownloadedImageDTO;
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
        Storage::disk('public')->put('products/123.jpg', 'pre-existing-jpg');

        $this->clientMock->shouldNotReceive('searchAndDownload');
        $this->productSourceClientMock->shouldNotReceive('getProduct');

        $result = $this->sut->execute('123', 'Tomate');

        $this->assertSame('products/123.jpg', $result);
        $this->assertSame('pre-existing-jpg', Storage::disk('public')->get('products/123.jpg'));
    }

    public function test_it_searches_downloads_and_stores_image_in_its_plain_format(): void
    {
        $plainBytes = 'plain-png-raw-binary-content';

        $this->clientMock->shouldReceive('searchAndDownload')
            ->once()
            ->with('Tomate produto isolado fundo branco')
            ->andReturn(new DownloadedImageDTO(content: $plainBytes, extension: 'png'));

        $result = $this->sut->execute('123', 'Tomate');

        $this->assertSame('products/123.png', $result);
        $this->assertTrue(Storage::disk('public')->exists('products/123.png'));
        $this->assertSame($plainBytes, Storage::disk('public')->get('products/123.png'));
    }

    public function test_it_fetches_product_name_from_get_product_action_if_not_provided(): void
    {
        $plainBytes = 'plain-jpg-raw-binary-content';

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
            ->andReturn(new DownloadedImageDTO(content: $plainBytes, extension: 'jpg'));

        $result = $this->sut->execute('456');

        $this->assertSame('products/456.jpg', $result);
        $this->assertTrue(Storage::disk('public')->exists('products/456.jpg'));
        $this->assertSame($plainBytes, Storage::disk('public')->get('products/456.jpg'));
    }

    public function test_it_logs_warning_and_stores_placeholder_when_download_fails(): void
    {
        Log::spy();

        $this->clientMock->shouldReceive('searchAndDownload')
            ->once()
            ->with('Abacaxi produto isolado fundo branco')
            ->andReturn(null);

        $result = $this->sut->execute('789', 'Abacaxi');

        $this->assertSame('products/789.jpg', $result);
        $this->assertTrue(Storage::disk('public')->exists('products/789.jpg'));

        Log::shouldHaveReceived('warning')
            ->once()
            ->with(Mockery::pattern('/Failed to populate product image/'));
    }
}
