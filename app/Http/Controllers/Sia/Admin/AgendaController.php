<?php

namespace App\Http\Controllers\Sia\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sia\AgendaRequest;
use App\Models\AgendaCategory;
use App\Models\AgendaDay;
use App\Models\AuditLog;
use App\Services\Sia\AgendaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgendaController extends Controller
{
    public function __construct(private AgendaService $service)
    {
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = AgendaDay::query()->withCount('items')->orderByDesc('agenda_date')->orderByDesc('id');

        if ($q !== '') {
            $query->where('title', 'like', "%{$q}%");
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date')) ? $request->query('date') : null;
        if ($date) {
            $query->whereDate('agenda_date', $date);
        }

        return Inertia::render('Sia/Admin/Agenda/Index', [
            'agendas' => $query->paginate(15)->withQueryString()->through(fn ($d) => $d->toSummary()),
            'filters' => ['q' => $q, 'status' => $request->query('status'), 'date' => $date],
        ]);
    }

    public function create()
    {
        return Inertia::render('Sia/Admin/Agenda/Create', [
            'categories' => $this->categories(),
            'agenda' => [
                'title' => 'Agenda Kegiatan Sekolah', 'agenda_date' => now(config('sia.timezone'))->format('Y-m-d'),
                'introduction' => null, 'recipients' => null, 'uniform_information' => null, 'general_notes' => null,
                'status' => 'draft', 'show_on_website' => true, 'show_on_tv' => true, 'items' => [],
            ],
        ]);
    }

    public function store(AgendaRequest $request)
    {
        $day = $this->service->save(null, $request->validated(), $request->user()->id);
        AuditLog::record('create', 'agenda', 'Membuat agenda "' . $day->title . '" (' . $day->agenda_date->format('d/m/Y') . ').');

        return redirect()->route('admin.agenda.edit', $day)->with('success', 'Agenda disimpan.');
    }

    public function edit(AgendaDay $agenda)
    {
        return Inertia::render('Sia/Admin/Agenda/Edit', [
            'agenda' => $this->service->toForm($agenda),
            'categories' => $this->categories(),
        ]);
    }

    public function update(AgendaRequest $request, AgendaDay $agenda)
    {
        $before = $agenda->status;
        $day = $this->service->save($agenda, $request->validated(), $request->user()->id);
        $note = $before !== $day->status ? ' Status: ' . $before . ' → ' . $day->status . '.' : '';
        AuditLog::record('update', 'agenda', 'Memperbarui agenda "' . $day->title . '" (' . $day->agenda_date->format('d/m/Y') . ').' . $note);

        return back()->with('success', $day->status === 'published' ? 'Agenda diterbitkan.' : 'Perubahan disimpan.');
    }

    public function destroy(AgendaDay $agenda)
    {
        AuditLog::record('delete', 'agenda', 'Menghapus agenda "' . $agenda->title . '" (' . $agenda->agenda_date->format('d/m/Y') . ').');
        $agenda->delete();

        return redirect()->route('admin.agenda.index')->with('success', 'Agenda dihapus.');
    }

    public function duplicate(Request $request, AgendaDay $agenda)
    {
        $copy = $this->service->duplicate($agenda, $request->user()->id);
        AuditLog::record('duplicate', 'agenda', 'Menduplikasi agenda "' . $agenda->title . '".');

        return redirect()->route('admin.agenda.edit', $copy)->with('success', 'Agenda diduplikasi sebagai draft. Atur ulang tanggalnya sebelum menerbitkan.');
    }

    public function status(Request $request, AgendaDay $agenda)
    {
        $data = $request->validate(['status' => ['required', 'in:draft,published,archived']]);

        if ($data['status'] === 'published' && $agenda->items()->count() === 0) {
            return back()->with('error', 'Agenda belum memiliki kegiatan, sehingga tidak dapat diterbitkan.');
        }

        $agenda->update([
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? ($agenda->published_at ?: now()) : null,
            'updated_by' => $request->user()->id,
        ]);
        AuditLog::record('status', 'agenda', 'Mengubah status agenda "' . $agenda->title . '" menjadi ' . $data['status'] . '.');

        return back()->with('success', ['published' => 'Agenda diterbitkan.', 'draft' => 'Agenda dikembalikan ke draft.', 'archived' => 'Agenda diarsipkan.'][$data['status']]);
    }

    private function categories()
    {
        return AgendaCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'color']);
    }
}
