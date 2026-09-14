<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // If no roles specified, allow
        if (empty($roles)) {
            return $next($request);
        }

        $userRole = $user->role ? $user->role->name : null;

        if (!$userRole || !in_array($userRole, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: You do not have the required role to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}
