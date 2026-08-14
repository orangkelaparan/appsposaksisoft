<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (session('user_role') === 'Super Administrator') {
            return $next($request);
        }

        $allowed = DB::table('user_roles')
            ->join('role_permissions', 'role_permissions.role_id', '=', 'user_roles.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('user_roles.user_id', session('user_id'))
            ->where('permissions.slug', $permission)
            ->exists();

        if (! $allowed) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to perform this action.'], 403);
            }

            abort(403, 'You do not have permission to access this module.');
        }

        return $next($request);
    }
}
