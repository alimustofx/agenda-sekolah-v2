<?php

namespace App\Http\Middleware\Sia;

use App\Models\SchoolSetting;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShareSiaData
{
    public function handle(Request $request, Closure $next)
    {
        Inertia::share('sia', fn () => [
            'school' => (function () {
                $s = SchoolSetting::current();

                return [
                    'name' => $s->school_name,
                    'tagline' => $s->tagline,
                    'address' => $s->school_address,
                    'logo_url' => $s->school_logo ? asset('storage/' . $s->school_logo) : null,
                ];
            })(),
            'user' => $request->user() ? [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
                'is_super_admin' => $request->user()->role === 'super_admin',
            ] : null,
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);

        return $next($request);
    }
}
