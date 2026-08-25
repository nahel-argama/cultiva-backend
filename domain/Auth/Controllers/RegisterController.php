<?php

namespace Cultiva\Auth\Controllers;

use Cultiva\Auth\Actions\SignUpAction;
use Cultiva\Auth\DTO\SignUpDTO;
use Cultiva\Auth\Requests\SignUpRequest;
use Cultiva\Auth\Transformers\SignUpTransformer;
use Cultiva\Base\Contracts\Controller;
use Illuminate\Http\JsonResponse;

final class RegisterController extends Controller
{

    public function signUp(
        SignUpRequest $request,
        SignUpAction $action,
        SignUpTransformer $transformer,
    ): JsonResponse {
        $dto = SignUpDTO::fromArray($request->validated());

        $result = $action->execute($dto);

        $data = $transformer->transform($result);

        return response()->json([
            'data' => $data,
        ], 201);
    }
}
