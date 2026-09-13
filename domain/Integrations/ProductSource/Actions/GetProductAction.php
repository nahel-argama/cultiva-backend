<?php

namespace Cultiva\Integrations\ProductSource\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\ProductSource\Clients\ProductSourceClient;
use Cultiva\Integrations\ProductSource\DTO\ProductDTO;
use Illuminate\Support\Facades\Lang;
use Throwable;

final class GetProductAction
{
    public function __construct(
        private readonly ProductSourceClient $client,
    ) {}

    public function execute(string $sourceProductId): ProductDTO
    {
        try {
            $response = $this->client->getProduct($sourceProductId);
        } catch (Throwable $exception) {
            throw new CultivaException(503, Lang::get('integrations.product_source.unavailable'), $exception);
        }

        if ($response->status() === 404) {
            throw new CultivaException(422, Lang::get('integrations.product_source.not_found'));
        }

        if (! $response->successful()) {
            throw new CultivaException(503, Lang::get('integrations.product_source.unavailable'));
        }

        return ProductDTO::from($response->json());
    }
}
