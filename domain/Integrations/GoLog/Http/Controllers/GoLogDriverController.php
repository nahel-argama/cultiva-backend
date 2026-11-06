<?php

namespace Cultiva\Integrations\GoLog\Http\Controllers;

use Cultiva\Auth\Transformers\UserAuthTransformer;
use Cultiva\Base\Contracts\Controller;
use Cultiva\Integrations\GoLog\Actions\CreateGoLogDriverAction;
use Cultiva\Integrations\GoLog\Actions\CreateGoLogEquipamentGroupAction;
use Cultiva\Integrations\GoLog\Actions\CreateGoLogUserAction;
use Cultiva\Integrations\GoLog\Actions\CreateGoLogVehicleAction;
use Cultiva\Integrations\GoLog\Actions\CreateGoLogWorkScheduleAction;
use Cultiva\Integrations\GoLog\DTO\CreateGoLogDriverDTO;
use Cultiva\Integrations\GoLog\DTO\CreateGoLogEquipamentGroupDTO;
use Cultiva\Integrations\GoLog\DTO\CreateGoLogUserDTO;
use Cultiva\Integrations\GoLog\DTO\CreateGoLogVehicleDTO;
use Cultiva\Integrations\GoLog\DTO\CreateGoLogWorkScheduleDTO;
use Cultiva\Integrations\GoLog\Http\Requests\CreateGoLogDriverRequest;
use Cultiva\Integrations\GoLog\Http\Requests\CreateGoLogEquipamentGroupRequest;
use Cultiva\Integrations\GoLog\Http\Requests\CreateGoLogUserRequest;
use Cultiva\Integrations\GoLog\Http\Requests\CreateGoLogVehicleRequest;
use Cultiva\Integrations\GoLog\Http\Requests\CreateGoLogWorkScheduleRequest;
use Cultiva\Models\Delivery\Transformers\GoLogDriverTransformer;
use Illuminate\Http\JsonResponse;

final class GoLogDriverController extends Controller
{
    public function storeUser(
        CreateGoLogUserRequest $request,
        CreateGoLogUserAction $action,
        UserAuthTransformer $transformer,
    ): JsonResponse {
        $dto = CreateGoLogUserDTO::from($request->validated());
        $result = $action->execute($dto);

        return response()->json([
            'data' => $transformer->transform($result),
        ], 201);
    }

    public function storeDriver(
        CreateGoLogDriverRequest $request,
        CreateGoLogDriverAction $action,
        GoLogDriverTransformer $transformer,
    ): JsonResponse {
        $driver = $request->user()->gologDriver()->firstOrFail();
        $dto = CreateGoLogDriverDTO::from($request->validated());
        $updatedDriver = $action->execute($driver, $dto);

        return response()->json([
            'data' => $transformer->transform($updatedDriver),
        ], 201);
    }

    public function storeVehicle(
        CreateGoLogVehicleRequest $request,
        CreateGoLogVehicleAction $action,
        GoLogDriverTransformer $transformer,
    ): JsonResponse {
        $driver = $request->user()->gologDriver()->firstOrFail();
        $dto = CreateGoLogVehicleDTO::from($request->validated());
        $updatedDriver = $action->execute($driver, $dto);

        return response()->json([
            'data' => $transformer->transform($updatedDriver),
        ], 201);
    }

    public function storeEquipamentGroup(
        CreateGoLogEquipamentGroupRequest $request,
        CreateGoLogEquipamentGroupAction $action,
        GoLogDriverTransformer $transformer,
    ): JsonResponse {
        $driver = $request->user()->gologDriver()->firstOrFail();
        $dto = CreateGoLogEquipamentGroupDTO::from($request->validated());
        $updatedDriver = $action->execute($driver, $dto);

        return response()->json([
            'data' => $transformer->transform($updatedDriver),
        ], 201);
    }

    public function storeWorkSchedule(
        CreateGoLogWorkScheduleRequest $request,
        CreateGoLogWorkScheduleAction $action,
        GoLogDriverTransformer $transformer,
    ): JsonResponse {
        $driver = $request->user()->gologDriver()->firstOrFail();
        $dto = CreateGoLogWorkScheduleDTO::from($request->validated());
        $updatedDriver = $action->execute($driver, $dto);

        return response()->json([
            'data' => $transformer->transform($updatedDriver),
        ], 201);
    }
}
