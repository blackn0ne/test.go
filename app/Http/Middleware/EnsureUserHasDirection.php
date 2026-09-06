<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasDirection
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->isAdmin() || $user->direction_id !== null) {
            return $next($request);
        }

        if ($request->routeIs(
            'direction.update',
            'logout',
            'dashboard',
            'profile.edit',
            'profile.update',
            'profile.destroy',
            'security.edit',
            'user-password.update',
            'appearance.edit',
        )) {
            return $next($request);
        }

        if ($request->header('X-Inertia')) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('dashboard');
    }
}
