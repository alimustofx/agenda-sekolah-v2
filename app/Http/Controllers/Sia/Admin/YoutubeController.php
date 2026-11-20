<?php

namespace App\Http\Controllers\Sia\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\YoutubeSetting;
use App\Models\YoutubeSource;
use App\Services\Sia\YoutubeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class YoutubeController extends Controller
{
    public function index()
    {
        return Inertia::render('Sia/Admin/Youtube/Index', [
            'sources' => YoutubeSource::query()->orderBy('display_order')->orderBy('id')->get(),
            'settings' => YoutubeSetting::current(),
            'hasApiKey' => (bool) config('sia.youtube.api_key'),
        ]);
    }

    public function store(Request $request, YoutubeService $yt)
    {
        $data = $request->validate([
            'url' => ['required', 'string', 'max:500'],
            'type' => ['required', 'in:video,playlist'],
            'title' => ['nullable', 'string', 'max:250'],
        ], ['url.required' => 'Tempelkan URL YouTube.']);

        $parsed = $yt->parseUrl($data['url']);
        if (! $parsed) {
            return back()->withErrors(['url' => 'URL bukan tautan video atau playlist YouTube yang valid.']);
        }
        if ($data['type'] === 'video' && ! $parsed['video_id']) {
            return back()->withErrors(['url' => 'URL ini tidak memuat video. Pilih jenis "Playlist" atau gunakan tautan video.']);
        }
        if ($data['type'] === 'playlist' && ! $parsed['playlist_id']) {
            return back()->withErrors(['url' => 'URL ini tidak memuat playlist. Gunakan tautan yang berisi parameter "list=".']);
        }

        $videoId = $data['type'] === 'video' ? $parsed['video_id'] : null;
        $canonical = $data['type'] === 'video'
            ? 'https://www.youtube.com/watch?v=' . $videoId
            : 'https://www.youtube.com/playlist?list=' . $parsed['playlist_id'];
        $meta = $yt->metadata($canonical, $videoId);

        $source = YoutubeSource::create([
            'source_type' => $data['type'],
            'title' => $data['title'] ?: ($meta['title'] ?: ($data['type'] === 'video' ? 'Video YouTube' : 'Playlist YouTube')),
            'channel' => $meta['channel'],
            'youtube_url' => $canonical,
            'youtube_id' => $videoId,
            'playlist_id' => $data['type'] === 'playlist' ? $parsed['playlist_id'] : null,
            'thumbnail_url' => $meta['thumbnail_url'],
            'duration' => $meta['duration'],
            'display_order' => (int) YoutubeSource::max('display_order') + 1,
            'is_active' => true,
        ]);
        AuditLog::record('create', 'youtube', 'Menambah ' . $data['type'] . ' YouTube "' . $source->title . '".');

        return back()->with('success', ($data['type'] === 'video' ? 'Video' : 'Playlist') . ' ditambahkan.');
    }

    public function update(Request $request, YoutubeSource $source)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:250'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $source->update($data);
        AuditLog::record('update', 'youtube', 'Memperbarui sumber YouTube "' . $source->title . '".');

        return back()->with('success', 'Sumber diperbarui.');
    }

    public function destroy(YoutubeSource $source)
    {
        AuditLog::record('delete', 'youtube', 'Menghapus sumber YouTube "' . $source->title . '".');
        $source->delete();

        return back()->with('success', 'Sumber dihapus.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'exists:youtube_sources,id']]);
        foreach ($data['ids'] as $i => $id) {
            YoutubeSource::whereKey($id)->update(['display_order' => $i + 1]);
        }
        AuditLog::record('reorder', 'youtube', 'Mengubah urutan sumber YouTube.');

        return back()->with('success', 'Urutan disimpan.');
    }

    public function settings(Request $request)
    {
        $data = $request->validate([
            'playback_enabled' => ['boolean'], 'autoplay' => ['boolean'], 'muted' => ['boolean'],
            'volume' => ['required', 'integer', 'between:0,100'], 'shuffle' => ['boolean'], 'loop' => ['boolean'],
            'intermission_enabled' => ['boolean'], 'intermission_duration' => ['required', 'integer', 'between:5,300'],
            'playback_mode' => ['required', 'in:single,queue,playlist,mixed'],
        ]);
        YoutubeSetting::current()->update($data);
        AuditLog::record('update', 'youtube', 'Memperbarui pengaturan pemutaran YouTube.');

        return back()->with('success', 'Pengaturan pemutaran disimpan.');
    }
}
