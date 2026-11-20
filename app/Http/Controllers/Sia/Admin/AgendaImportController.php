<?php

namespace App\Http\Controllers\Sia\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sia\AgendaRequest;
use App\Models\AgendaCategory;
use App\Models\AgendaDay;
use App\Models\AuditLog;
use App\Models\ParserLog;
use App\Services\Parser\AgendaParserManager;
use App\Services\Sia\AgendaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgendaImportController extends Controller
{
    public function create()
    {
        return Inertia::render('Sia/Admin/Agenda/Import');
    }

    public function store(Request $request, AgendaParserManager $parser)
    {
        $data = $request->validate(['text' => ['required', 'string', 'min:20', 'max:30000']], [
            'text.required' => 'Tempelkan teks agenda dari WhatsApp terlebih dahulu.',
            'text.min' => 'Teks terlalu pendek untuk dibaca sebagai agenda.',
        ]);

        $result = $parser->parse($data['text']);
        $log = ParserLog::create([
            'user_id' => $request->user()->id,
            'original_text' => $data['text'],
            'parser_type' => $result['parser_type'],
            'parsing_result' => $result,
            'confidence_summary' => $result['summary'],
        ]);
        AuditLog::record('parse', 'agenda', 'Memproses teks WhatsApp menjadi agenda (' . count($result['items']) . ' kegiatan terdeteksi).');

        return redirect()->route('admin.agenda.import.review', $log);
    }

    public function review(ParserLog $log)
    {
        $result = $log->parsing_result;
        $date = $result['agenda']['agenda_date'] ?? null;

        return Inertia::render('Sia/Admin/Agenda/ImportReview', [
            'logId' => $log->id,
            'original' => $log->original_text,
            'parserType' => $log->parser_type,
            'greeting' => $result['greeting'] ?? null,
            'agenda' => $result['agenda'] + ['status' => 'draft', 'show_on_website' => true, 'show_on_tv' => true, 'items' => $result['items']],
            'statuses' => $result['statuses'],
            'summary' => $result['summary'],
            'existing' => $date ? AgendaDay::query()->whereDate('agenda_date', $date)->get(['id', 'title', 'status'])->all() : [],
            'categories' => AgendaCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function save(AgendaRequest $request, ParserLog $log, AgendaService $service)
    {
        $data = $request->validated();
        $data['status'] = 'draft'; // hasil parsing tidak pernah terbit otomatis.
        $data['original_source_text'] = $log->original_text;

        $day = $service->save(null, $data, $request->user()->id);
        $log->update(['agenda_day_id' => $day->id]);
        AuditLog::record('create', 'agenda', 'Menyimpan draft agenda dari import WhatsApp: "' . $day->title . '".');

        return redirect()->route('admin.agenda.edit', $day)->with('success', 'Draft agenda disimpan. Periksa lalu terbitkan bila sudah benar.');
    }
}
