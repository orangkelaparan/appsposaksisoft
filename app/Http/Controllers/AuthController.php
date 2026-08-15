<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (session()->has('user_id')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request, AuditService $audit): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        $key = strtolower($credentials['login']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['login' => 'Too many login attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.'])->onlyInput('login');
        }

        $user = DB::table('users')->where('email', $credentials['login'])->orWhere('username', $credentials['login'])->first();
        if (! $user || $user->status !== 'active' || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 60);
            $audit->record('login_failed', 'security', 'user', $user?->id, null, ['login' => $credentials['login']]);

            return back()->withErrors(['login' => 'The supplied credentials are incorrect or the account is inactive.'])->onlyInput('login');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $role = DB::table('user_roles')->join('roles', 'roles.id', '=', 'user_roles.role_id')->where('user_roles.user_id', $user->id)->value('roles.name') ?? 'User';
        $primaryStoreId = DB::table('user_stores')->where('user_id', $user->id)->orderByDesc('is_primary')->value('store_id');
        $request->session()->put(['user_id' => $user->id, 'user_name' => $user->name, 'user_role' => $role, 'active_store_id' => $primaryStoreId]);
        DB::table('users')->where('id', $user->id)->update(['last_login_at' => now()]);
        $audit->record('login', 'security', 'user', $user->id);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request, AuditService $audit): RedirectResponse
    {
        $audit->record('logout', 'security', 'user', session('user_id'));
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been signed out securely.');
    }
}
