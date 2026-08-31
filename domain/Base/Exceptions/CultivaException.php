<?php

namespace Cultiva\Base\Exceptions;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class CultivaException extends HttpException
{

    public function __construct(
        private int $statusCode,
        string $message = '',
        ?\Throwable $previous = null,
        private array $context = [],
    ) {}

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'context' => $this->context,
        ], $this->getStatusCode());
    }
}
