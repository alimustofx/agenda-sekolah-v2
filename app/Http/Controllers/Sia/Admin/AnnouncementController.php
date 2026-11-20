<?php

namespace App\Http\Controllers\Sia\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('status', 'aktif');
        $query = Announcement::query()->byPriority();

        if ($filter === 'aktif') {
            $query->active();
        } elseif ($filter === 'arsip') {
            $query->expired();
        } elseif ($filter === 'terjadwal') {
            $query->where('is_active', true)->where('start_at', '>', now(config('sia.timezone'))->toDateTimeString());
        }

        return Inertia::render('Sia/Admin/Announcements/Index', [
            'announcements' => $query->paginate(12)->withQueryString()->through(fn ($a) => $a->toForm()),
            'filter' => $filter,
        ]);
    }

    public function store(Request $request)
    {
        $a = Announcement::create($this->validated($request) + ['created_by' => $request->user()->id]);
        AuditLog::record('create', 'pengumuman', 'Menerbitkan pengumuman "' . $a->title . '".');

        return back()->with('success', 'Pengumuman disimpan.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $announcement->update($this->validated($request));
        AuditLog::record('update', 'pengumuman', 'Memperbarui pengumuman "' . $announcement->title . '".');

        return back()->with('success', 'Pengumuman diperbarui.');
    }

    public function destroy(Announcement $announcement)
    {
        AuditLog::record('delete', 'pengumuman', 'Menghapus pengumuman "' . $announcement->title . '".');
        $announcement->delete();

        return back()->with('success', 'Pengumuman dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string', 'max:3000'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', 'in:normal,important,urgent'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'show_on_website' => ['boolean'],
            'show_on_tv' => ['boolean'],
            'is_active' => ['boolean'],
        ]);
    }
}
