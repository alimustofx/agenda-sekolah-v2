<?php

namespace App\Http\Controllers\Sia\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DisplaySetting;
use App\Models\RunningText;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DisplaySettingController extends Controller
{
    public function index()
    {
        return Inertia::render('Sia/Admin/Display/Index', ['settings' => DisplaySetting::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'display_title' => ['required', 'string', 'max:100'],
            'agenda_cards_per_page' => ['required', 'integer', 'between:1,4'],
            'pagination_interval' => ['required', 'integer', 'between:5,120'],
            'pagination_min' => ['required', 'integer', 'between:5,120'],
            'pagination_max' => ['required', 'integer', 'between:5,180', 'gte:pagination_min'],
            'adaptive_pagination' => ['boolean'],
            'video_panel_enabled' => ['boolean'],
            'information_panel_enabled' => ['boolean'],
            'weather_enabled' => ['boolean'],
            'running_text_enabled' => ['boolean'],
            'panel_modes' => ['array'],
            'panel_modes.*' => ['in:announcement,activity,school_info,weather'],
            'school_info_text' => ['nullable', 'string', 'max:1000'],
            'refresh_interval' => ['required', 'integer', 'between:10,600'],
        ]);
        DisplaySetting::current()->update($data);
        AuditLog::record('update', 'tv', 'Memperbarui pengaturan TV Display.');

        return back()->with('success', 'Pengaturan TV Display disimpan.');
    }

    public function runningTexts()
    {
        return Inertia::render('Sia/Admin/Display/RunningText', [
            'texts' => RunningText::query()->orderBy('display_order')->orderBy('id')->get()->map->toForm(),
        ]);
    }

    public function storeText(Request $request)
    {
        $data = $this->validatedText($request);
        $data['display_order'] = $data['display_order'] ?? ((int) RunningText::max('display_order') + 1);
        RunningText::create($data);
        AuditLog::record('create', 'tv', 'Menambah running text.');

        return back()->with('success', 'Running text disimpan.');
    }

    public function updateText(Request $request, RunningText $text)
    {
        $text->update($this->validatedText($request));
        AuditLog::record('update', 'tv', 'Memperbarui running text.');

        return back()->with('success', 'Running text diperbarui.');
    }

    public function destroyText(RunningText $text)
    {
        AuditLog::record('delete', 'tv', 'Menghapus running text.');
        $text->delete();

        return back()->with('success', 'Running text dihapus.');
    }

    private function validatedText(Request $request): array
    {
        return $request->validate([
            'content' => ['required', 'string', 'max:300'],
            'speed' => ['required', 'integer', 'between:30,300'],
            'text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'priority' => ['required', 'in:normal,important,urgent'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
