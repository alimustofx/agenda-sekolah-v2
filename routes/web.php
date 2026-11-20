<?php

use Illuminate\Support\Facades\Route;

// Seluruh route Sistem Agenda Sekolah (publik, TV Display, dan dashboard admin).
require __DIR__ . '/sia.php';

// Fortify/starter kit mengarahkan login ke /dashboard; teruskan ke dashboard admin.
Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))->middleware('auth')->name('dashboard');

// Pengaturan akun bawaan starter kit (profil, kata sandi, 2FA) tetap dipakai.
if (file_exists(__DIR__ . '/settings.php')) {
    require __DIR__ . '/settings.php';
}
