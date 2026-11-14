<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Http\Controllers;

use Cultiva\Base\Contracts\Controller;
use Cultiva\Integrations\GoLog\Actions\ReceiveGoLogWebhookAction;
use Cultiva\Integrations\GoLog\Config;
use Cultiva\Integrations\GoLog\Enums\GoLogWebhookEventType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class GoLogWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        Config $config,
        ReceiveGoLogWebhookAction $action,
    ): JsonResponse {
        $secret = $config->getWebhookSecret();

        if ($secret === null || $secret === '') {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $signature = $request->header('X-GoLog-Signature');
        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if (! is_string($signature) || ! hash_equals($expectedSignature, $signature)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        $deliveryId = $request->header('X-GoLog-Delivery');

        if (! is_string($deliveryId) || ! Str::isUuid($deliveryId)) {
            return response()->json(['message' => 'Invalid X-GoLog-Delivery header.'], 422);
        }

        $eventHeader = $request->header('X-GoLog-Event') ?? $request->input('event');
        $eventType = is_string($eventHeader) ? GoLogWebhookEventType::tryFrom($eventHeader) : null;

        if ($eventType === null) {
            return response()->json(['message' => 'Unsupported GoLog webhook event.'], 422);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();

        $action->execute($deliveryId, $eventType, $payload);

        return response()->json(['status' => 'accepted'], 200);
    }
}
