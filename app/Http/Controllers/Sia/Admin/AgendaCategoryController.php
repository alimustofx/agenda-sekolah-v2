<?php

namespace App\Http\Controllers\Sia\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgendaCategory;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AgendaCategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Sia/Admin/Agenda/Categories', [
            'categories' => AgendaCategory::query()->withCount('items')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $cat = AgendaCategory::create($data);
        AuditLog::record('create', 'kategori', 'Menambah kategori "' . $cat->name . '".');

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, AgendaCategory $category)
    {
        $category->update($this->validated($request));
        AuditLog::record('update', 'kategori', 'Memperbarui kategori "' . $category->name . '".');

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(AgendaCategory $category)
    {
        AuditLog::record('delete', 'kategori', 'Menghapus kategori "' . $category->name . '".');
        $category->delete();

        return back()->with('success', 'Kategori dihapus. Kegiatan yang memakainya menjadi tanpa kategori.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['boolean'],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name) ?: 'kategori';
        $base = $slug;
        $i = 2;
        while (AgendaCategory::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
