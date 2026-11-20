<?php

namespace App\Http\Middleware\Sia;

use Closure;
use Illuminate\Http\Request;

/** Header keamanan dasar untuk halaman admin. */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Cache-Control', 'no-store, private'); // halaman admin tidak boleh tampil lagi lewat tombol Back setelah logout

        return $response;
    }
}
