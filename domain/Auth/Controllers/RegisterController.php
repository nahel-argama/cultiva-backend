<?php

namespace Cultiva\Auth\Controllers;

use Cultiva\Auth\Actions\GetSignupMetadataAction;
use Cultiva\Auth\Actions\SignUpAction;
use Cultiva\Auth\Actions\UploadDocumentAction;
use Cultiva\Auth\DTO\SignUpDTO;
use Cultiva\Auth\DTO\UploadDocumentDTO;
use Cultiva\Auth\Requests\SignUpRequest;
use Cultiva\Auth\Requests\UploadDocumentRequest;
use Cultiva\Auth\Transformers\SignupMetadataTransformer;
use Cultiva\Auth\Transformers\UserAuthTransformer;
use Cultiva\Base\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Lang;

final class RegisterController extends Controller
{
    public function signUp(
        SignUpRequest $request,
        SignUpAction $action,
        UserAuthTransformer $transformer,
    ): JsonResponse {
        $dto = SignUpDTO::fromArray($request->validated());

        $result = $action->execute($dto);

        $data = $transformer->transform($result);

        return response()->json([
            'data' => $data,
        ], 201);
    }

    public function metadata(
        GetSignupMetadataAction $action,
        SignupMetadataTransformer $transformer,
    ): JsonResponse {
        $result = $action->execute();

        return response()->json([
            'data' => $transformer->transform($result),
        ], 200);
    }

    public function uploadDocument(
        UploadDocumentRequest $request,
        UploadDocumentAction $action,
    ): JsonResponse {
        $user = $request->user();
        $dto = UploadDocumentDTO::from($user, $request->validated());

        $action->execute($dto);

        return response()->json([
            'message' => Lang::get('auth.document_verification.queued'),
        ], 202);
    }
}
