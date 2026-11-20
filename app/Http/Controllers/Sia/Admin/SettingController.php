<?php

namespace App\Http\Controllers\Sia\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SchoolSetting;
use App\Models\WeatherSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $school = SchoolSetting::current();

        return Inertia::render('Sia/Admin/Settings/Index', [
            'school' => $school->only(['school_name', 'tagline', 'school_address', 'website_url', 'primary_color', 'secondary_color']) + [
                'logo_url' => $school->school_logo ? asset('storage/' . $school->school_logo) : null,
            ],
            'weather' => WeatherSetting::current(),
            'ai' => ['enabled' => (bool) config('sia.ai_parser.enabled')],
        ]);
    }

    public function updateSchool(Request $request)
    {
        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:200'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'website_url' => ['nullable', 'url', 'max:300'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'remove_logo' => ['boolean'],
        ]);

        $school = SchoolSetting::current();
        if ($request->hasFile('logo')) {
            if ($school->school_logo) {
                Storage::disk('public')->delete($school->school_logo);
            }
            $data['school_logo'] = $request->file('logo')->store('logo', 'public');
        } elseif ($request->boolean('remove_logo') && $school->school_logo) {
            Storage::disk('public')->delete($school->school_logo);
            $data['school_logo'] = null;
        }
        unset($data['logo'], $data['remove_logo']);

        $school->update($data);
        AuditLog::record('update', 'pengaturan', 'Memperbarui identitas sekolah.');

        return back()->with('success', 'Identitas sekolah disimpan.');
    }

    public function updateWeather(Request $request)
    {
        $data = $request->validate([
            'location' => ['required', 'string', 'max:200'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'is_enabled' => ['boolean'],
            'cache_duration' => ['required', 'integer', 'between:5,360'],
        ]);
        WeatherSetting::current()->update($data);
        AuditLog::record('update', 'pengaturan', 'Memperbarui integrasi cuaca.');

        return back()->with('success', 'Pengaturan cuaca disimpan.');
    }
}
