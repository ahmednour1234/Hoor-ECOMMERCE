<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate-keeps the admin dashboard.
 *
 * Authentication is handled by the `auth` middleware that runs before this one,
 * so a missing user here means the route was misconfigured rather than that the
 * visitor is a guest — we still fail closed.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->canAccessAdmin()) {
            abort(403, __('admin.errors.forbidden'));
        }

        return $next($request);
    }
}
