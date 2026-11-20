<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

/**
 * Halaman register bawaan starter kit tetap ada. Akun yang mendaftar sendiri TIDAK boleh
 * otomatis mendapat akses dashboard, jadi langsung dinonaktifkan sampai Super Admin mengaktifkannya.
 * (Terdeteksi otomatis oleh Laravel lewat type-hint Registered.)
 */
class LockSelfRegisteredUser
{
    public function handle(Registered $event): void
    {
        if (isset($event->user->id)) {
            DB::table('users')->where('id', $event->user->id)->update(['is_active' => false, 'role' => 'admin_humas']);
        }
    }
}
