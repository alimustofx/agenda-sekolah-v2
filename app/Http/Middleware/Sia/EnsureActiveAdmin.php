<?php

namespace App\Http\Middleware\Sia;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && (! $user->is_active || ! in_array($user->role, ['super_admin', 'admin_humas'], true))) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors(['email' => 'Akun ini dinonaktifkan. Hubungi Super Admin.']);
        }

        return $next($request);
    }
}
