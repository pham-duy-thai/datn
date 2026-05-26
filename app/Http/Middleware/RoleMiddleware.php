<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return redirect()->guest(route('login'));
            }

            return response()->json(['message' => 'Chưa xác thực.'], 401);
        }

        if (! in_array($user->role, $roles, true)) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                abort(403, 'Bạn không có quyền truy cập khu vực này.');
            }

            return response()->json(['message' => 'Bạn không có quyền thực hiện thao tác này.'], 403);
        }

        return $next($request);
    }
}
