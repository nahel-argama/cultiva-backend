<?php

namespace Cultiva\Integrations\ProductSource\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\ProductSource\DTO\ProductDTO;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Lang;
use Throwable;

final class GetProductAction
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    public function execute(string $sourceProductId): ProductDTO
    {
        try {
            $response = $this->http
                ->baseUrl(rtrim((string) config('services.product_source.base_url'), '/'))
                ->timeout((float) config('services.product_source.timeout', 5))
                ->get('products/'.rawurlencode($sourceProductId));
        } catch (Throwable $exception) {
            throw new CultivaException(503, Lang::get('integrations.product_source.unavailable'), $exception);
        }

        if ($response->status() === 404) {
            throw new CultivaException(422, Lang::get('integrations.product_source.not_found'));
        }

        if (! $response->successful()) {
            throw new CultivaException(503, Lang::get('integrations.product_source.unavailable'));
        }

        $id = $response->json('id');
        $name = $response->json('name');
        $normalName = $response->json('normal_name');
        $createdAt = $response->json('created_at');
        $resolvedName = is_string($name) && trim($name) !== '' ? trim($name) : $normalName;

        if (
            ! is_string($id)
            || $id !== $sourceProductId
            || ! is_string($resolvedName)
            || trim($resolvedName) === ''
            || ! is_string($createdAt)
            || trim($createdAt) === ''
        ) {
            throw new CultivaException(503, Lang::get('integrations.product_source.unavailable'));
        }

        return ProductDTO::from([
            'id' => $id,
            'name' => trim($resolvedName),
        ]);
    }
}
