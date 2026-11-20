<?php

namespace App\Http\Controllers\Sia\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SchoolActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SchoolActivityController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'berlangsung');
        $all = SchoolActivity::query()->orderByDesc('start_at')->orderByDesc('id')->limit(200)->get();
        $rows = $all->map->toForm();

        $list = $tab === 'riwayat'
            ? $rows->where('effective_status', 'completed')
            : $rows->whereIn('effective_status', ['ongoing', 'scheduled']);

        return Inertia::render('Sia/Admin/Activities/Index', ['activities' => $list->values(), 'tab' => $tab]);
    }

    public function store(Request $request)
    {
        $a = SchoolActivity::create($this->validated($request));
        AuditLog::record('create', 'kegiatan', 'Menambah kegiatan sekolah "' . $a->title . '".');

        return back()->with('success', 'Kegiatan disimpan.');
    }

    public function update(Request $request, SchoolActivity $activity)
    {
        $activity->update($this->validated($request));
        AuditLog::record('update', 'kegiatan', 'Memperbarui kegiatan sekolah "' . $activity->title . '".');

        return back()->with('success', 'Kegiatan diperbarui.');
    }

    public function destroy(SchoolActivity $activity)
    {
        AuditLog::record('delete', 'kegiatan', 'Menghapus kegiatan sekolah "' . $activity->title . '".');
        $activity->delete();

        return back()->with('success', 'Kegiatan dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:3000'],
            'location' => ['nullable', 'string', 'max:200'],
            'responsible_person' => ['nullable', 'string', 'max:200'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'time_description' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:scheduled,ongoing,completed'],
            'is_status_manual' => ['boolean'],
            'show_on_website' => ['boolean'],
            'show_on_tv' => ['boolean'],
        ]);

        return $data;
    }
}
