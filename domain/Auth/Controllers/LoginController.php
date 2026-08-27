<?php

namespace Cultiva\Auth\Controllers;

use Cultiva\Auth\Actions\LoginAction;
use Cultiva\Auth\DTO\LoginDTO;
use Cultiva\Auth\Requests\LoginRequest;
use Cultiva\Auth\Transformers\UserAuthTransformer;
use Cultiva\Base\Contracts\Controller;
use Illuminate\Http\JsonResponse;

final class LoginController extends Controller
{
    public function login(
        LoginRequest $request,
        LoginAction $action,
        UserAuthTransformer $transformer,
    ): JsonResponse {
        $dto = LoginDTO::fromArray($request->validated());

        $result = $action->execute($dto);

        $data = $transformer->transform($result);

        return response()->json([
            'data' => $data,
        ], 200);
    }
}
