<?php

namespace Cultiva\Integrations\Geo\Controllers;

use Cultiva\Base\Contracts\Controller;
use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\Actions\SearchCepAction;
use Illuminate\Http\JsonResponse;

final class GeoController extends Controller
{

    public function search(string $cep, SearchCepAction $action): JsonResponse
    {
        $result = $action->execute(new Cep($cep));

        return response()->json($result);
    }
}
