<?php

namespace Cultiva\Auth\Middleware;

use Closure;
use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\HttpFoundation\Response;

final class EnsureProfile
{
    public function handle(Request $request, Closure $next, string $profile): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->profile_type !== ProfileType::tryFrom($profile)) {
            throw new CultivaException(403, Lang::get('auth.forbidden'));
        }

        return $next($request);
    }
}
