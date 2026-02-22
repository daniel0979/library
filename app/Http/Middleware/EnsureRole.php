<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || ! $user->role || $user->role->name !== $role) {
            abort(403, 'You are not authorized to access this resource.');
        }

        return $next($request);
    }
}
