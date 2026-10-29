<?php

namespace Cultiva\Auth\Middleware;

use Closure;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new CultivaException(401, Lang::get('auth.unauthenticated'));
        }

        if (! $user->is_verified) {
            throw new CultivaException(403, Lang::get('auth.unverified'));
        }

        return $next($request);
    }
}
