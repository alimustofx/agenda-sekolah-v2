<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Login;

/** Terdeteksi otomatis oleh Laravel (event discovery) lewat type-hint Login. */
class RecordAdminLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;
        if (isset($user->id)) {
            \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update(['last_login_at' => now()]);
            AuditLog::record('login', 'auth', 'Masuk ke dashboard: ' . $user->email);
        }
    }
}
