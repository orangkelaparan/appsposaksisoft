<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->session()->get('user_id');
        $user = $userId ? DB::table('users')->where('id', $userId)->where('status', 'active')->first() : null;

        if (! $user) {
            $request->session()->forget(['user_id', 'user_name', 'user_role']);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login')->with('error', 'Please sign in to continue.');
        }

        app()->instance('currentUser', $user);

        return $next($request);
    }
}
