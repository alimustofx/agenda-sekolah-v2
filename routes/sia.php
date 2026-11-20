<?php

use App\Http\Controllers\Sia\Admin;
use App\Http\Controllers\Sia\DisplayController;
use App\Http\Controllers\Sia\PublicController;
use App\Http\Middleware\Sia\EnsureActiveAdmin;
use App\Http\Middleware\Sia\EnsureRole;
use App\Http\Middleware\Sia\ShareSiaData;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Website publik + TV Display (tanpa login)
|--------------------------------------------------------------------------
*/
Route::middleware(ShareSiaData::class)->group(function () {
    Route::get('/', [PublicController::class, 'home'])->name('home');
    Route::get('/agenda', [PublicController::class, 'agendaIndex'])->name('agenda.index');
    Route::get('/agenda/kalender', [PublicController::class, 'calendar'])->name('agenda.calendar');
    Route::get('/agenda/{agenda}', [PublicController::class, 'agendaShow'])->whereNumber('agenda')->name('agenda.show');
    Route::get('/pengumuman', [PublicController::class, 'announcements'])->name('announcements.index');
    Route::get('/pengumuman/{announcement}', [PublicController::class, 'announcementShow'])->whereNumber('announcement')->name('announcements.show');
    Route::get('/kegiatan', [PublicController::class, 'activities'])->name('activities.index');

    Route::get('/display', [DisplayController::class, 'index'])->name('display');
    Route::get('/display/data', [DisplayController::class, 'data'])->middleware('throttle:120,1')->name('display.data');
});

/*
|--------------------------------------------------------------------------
| Dashboard admin (Super Admin & Admin Humas)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', EnsureActiveAdmin::class, ShareSiaData::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Agenda
    Route::get('agenda/import', [Admin\AgendaImportController::class, 'create'])->name('agenda.import');
    Route::post('agenda/import', [Admin\AgendaImportController::class, 'store'])->name('agenda.import.store');
    Route::get('agenda/import/{log}/review', [Admin\AgendaImportController::class, 'review'])->name('agenda.import.review');
    Route::post('agenda/import/{log}', [Admin\AgendaImportController::class, 'save'])->name('agenda.import.save');

    Route::get('agenda/kategori', [Admin\AgendaCategoryController::class, 'index'])->name('agenda.categories');
    Route::post('agenda/kategori', [Admin\AgendaCategoryController::class, 'store'])->name('agenda.categories.store');
    Route::put('agenda/kategori/{category}', [Admin\AgendaCategoryController::class, 'update'])->name('agenda.categories.update');
    Route::delete('agenda/kategori/{category}', [Admin\AgendaCategoryController::class, 'destroy'])->name('agenda.categories.destroy');

    Route::get('agenda', [Admin\AgendaController::class, 'index'])->name('agenda.index');
    Route::get('agenda/create', [Admin\AgendaController::class, 'create'])->name('agenda.create');
    Route::post('agenda', [Admin\AgendaController::class, 'store'])->name('agenda.store');
    Route::get('agenda/{agenda}/edit', [Admin\AgendaController::class, 'edit'])->name('agenda.edit');
    Route::put('agenda/{agenda}', [Admin\AgendaController::class, 'update'])->name('agenda.update');
    Route::delete('agenda/{agenda}', [Admin\AgendaController::class, 'destroy'])->name('agenda.destroy');
    Route::post('agenda/{agenda}/duplicate', [Admin\AgendaController::class, 'duplicate'])->name('agenda.duplicate');
    Route::patch('agenda/{agenda}/status', [Admin\AgendaController::class, 'status'])->name('agenda.status');

    // Pengumuman & kegiatan sekolah
    Route::get('pengumuman', [Admin\AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('pengumuman', [Admin\AnnouncementController::class, 'store'])->name('announcements.store');
    Route::put('pengumuman/{announcement}', [Admin\AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('pengumuman/{announcement}', [Admin\AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    Route::get('kegiatan', [Admin\SchoolActivityController::class, 'index'])->name('activities.index');
    Route::post('kegiatan', [Admin\SchoolActivityController::class, 'store'])->name('activities.store');
    Route::put('kegiatan/{activity}', [Admin\SchoolActivityController::class, 'update'])->name('activities.update');
    Route::delete('kegiatan/{activity}', [Admin\SchoolActivityController::class, 'destroy'])->name('activities.destroy');

    // Media YouTube
    Route::get('youtube', [Admin\YoutubeController::class, 'index'])->name('youtube.index');
    Route::post('youtube', [Admin\YoutubeController::class, 'store'])->name('youtube.store');
    Route::post('youtube/urutan', [Admin\YoutubeController::class, 'reorder'])->name('youtube.reorder');
    Route::put('youtube/pengaturan', [Admin\YoutubeController::class, 'settings'])->name('youtube.settings');
    Route::patch('youtube/{source}', [Admin\YoutubeController::class, 'update'])->name('youtube.update');
    Route::delete('youtube/{source}', [Admin\YoutubeController::class, 'destroy'])->name('youtube.destroy');

    // TV Display
    Route::get('tv', [Admin\DisplaySettingController::class, 'index'])->name('tv.index');
    Route::put('tv', [Admin\DisplaySettingController::class, 'update'])->name('tv.update');
    Route::get('tv/running-text', [Admin\DisplaySettingController::class, 'runningTexts'])->name('tv.texts');
    Route::post('tv/running-text', [Admin\DisplaySettingController::class, 'storeText'])->name('tv.texts.store');
    Route::put('tv/running-text/{text}', [Admin\DisplaySettingController::class, 'updateText'])->name('tv.texts.update');
    Route::delete('tv/running-text/{text}', [Admin\DisplaySettingController::class, 'destroyText'])->name('tv.texts.destroy');

    // Audit log (Super Admin: semua; Admin Humas: aktivitas sendiri)
    Route::get('audit-log', [Admin\AuditLogController::class, 'index'])->name('audit');

    // Khusus Super Admin
    Route::middleware(EnsureRole::class . ':super_admin')->group(function () {
        Route::get('pengaturan', [Admin\SettingController::class, 'index'])->name('settings.index');
        Route::post('pengaturan/sekolah', [Admin\SettingController::class, 'updateSchool'])->name('settings.school');
        Route::put('pengaturan/cuaca', [Admin\SettingController::class, 'updateWeather'])->name('settings.weather');

        Route::get('administrator', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('administrator', [Admin\UserController::class, 'store'])->name('users.store');
        Route::put('administrator/{user}', [Admin\UserController::class, 'update'])->name('users.update');
        Route::delete('administrator/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
    });
});
