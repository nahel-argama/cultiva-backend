<?php

namespace Cultiva\Auth\Middleware;

use Closure;
use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\User\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureProfile
{
    public function handle(Request $request, Closure $next, string $profile): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->getProfileType() !== ProfileType::tryFrom($profile)) {
            abort(403);
        }

        return $next($request);
    }
}
