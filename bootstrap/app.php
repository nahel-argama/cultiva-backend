<?php

use Cultiva\Auth\Middleware\EnsureProfile;
use Cultiva\Base\Exceptions\CultivaException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->throttleWithRedis();
        $middleware->alias([
            'ability' => CheckForAnyAbility::class,
            'profile' => EnsureProfile::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(
            fn (AuthenticationException $exception, Request $request) => $request->expectsJson()
                ? response()->json(['message' => Lang::get('auth.unauthenticated')], 401)
                : null,
        );
        $exceptions->render(
            fn (AuthorizationException $exception, Request $request) => $request->expectsJson()
                ? response()->json(['message' => Lang::get('auth.forbidden')], 403)
                : null,
        );
        $exceptions->render(
            fn (HttpException $exception, Request $request) => $request->expectsJson()
                && $exception->getStatusCode() === 403
                    ? response()->json(['message' => Lang::get('auth.forbidden')], 403)
                    : null,
        );
        $exceptions->dontReport(CultivaException::class);
    })->create();
