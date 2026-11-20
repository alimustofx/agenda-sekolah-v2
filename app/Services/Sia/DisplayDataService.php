<?php

namespace App\Services\Sia;

use App\Models\AgendaDay;
use App\Models\Announcement;
use App\Models\DisplaySetting;
use App\Models\RunningText;
use App\Models\SchoolActivity;
use App\Models\SchoolSetting;
use App\Models\YoutubeSetting;
use App\Models\YoutubeSource;
use App\Support\SiaTime;

/** Sumber data tunggal untuk TV Display (render awal dan polling). */
class DisplayDataService
{
    public function __construct(private WeatherService $weather, private YoutubeService $youtube)
    {
    }

    public function build(): array
    {
        $now = SiaTime::now();
        $display = DisplaySetting::current();
        $yt = YoutubeSetting::current();

        $day = AgendaDay::query()->publishedFor('tv')
            ->whereDate('agenda_date', $now->toDateString())
            ->with(['items.category', 'items.schedules'])
            ->latest('updated_at')->first();

        $announcements = Announcement::query()->active('tv')->byPriority()->get()->map->toPublic()->values()->all();

        $activities = SchoolActivity::query()->where('show_on_tv', true)->get()
            ->filter(fn (SchoolActivity $a) => $a->effectiveStatus() === 'ongoing')
            ->map->toPublic()->values()->all();

        $texts = RunningText::query()->active()->orderBy('display_order')->orderBy('id')->get()->map(fn ($t) => [
            'id' => $t->id, 'content' => $t->content, 'speed' => $t->speed,
            'text_color' => $t->text_color, 'background_color' => $t->background_color,
        ])->values()->all();

        $sources = YoutubeSource::query()->where('is_active', true)->orderBy('display_order')->orderBy('id')->get()->map(fn ($s) => [
            'id' => $s->id,
            'type' => $s->source_type,
            'title' => $s->title,
            'video_id' => $s->youtube_id,
            'playlist_id' => $s->playlist_id,
            'videos' => $s->source_type === 'playlist' && $s->playlist_id ? $this->youtube->playlistVideos($s->playlist_id) : [],
        ])->values()->all();

        $school = SchoolSetting::current();

        $payload = [
            'school' => [
                'name' => $school->school_name,
                'tagline' => $school->tagline,
                'logo_url' => $school->school_logo ? asset('storage/' . $school->school_logo) : null,
            ],
            'display' => [
                'title' => $display->display_title,
                'cards_per_page' => max(1, min(6, (int) $display->agenda_cards_per_page)),
                'interval' => (int) $display->pagination_interval,
                'min_interval' => (int) $display->pagination_min,
                'max_interval' => (int) $display->pagination_max,
                'adaptive' => (bool) $display->adaptive_pagination,
                'video_panel' => (bool) $display->video_panel_enabled,
                'information_panel' => (bool) $display->information_panel_enabled,
                'running_text' => (bool) $display->running_text_enabled,
                'panel_modes' => $display->panel_modes ?: [],
                'school_info' => $display->school_info_text,
                'refresh_interval' => max(10, (int) $display->refresh_interval),
            ],
            'agenda' => $day ? $day->toPublic() : null,
            'announcements' => $announcements,
            'activities' => $activities,
            'running_texts' => $texts,
            'youtube' => [
                'settings' => [
                    'enabled' => (bool) $yt->playback_enabled,
                    'autoplay' => (bool) $yt->autoplay,
                    'muted' => (bool) $yt->muted,
                    'volume' => (int) $yt->volume,
                    'shuffle' => (bool) $yt->shuffle,
                    'loop' => (bool) $yt->loop,
                    'intermission' => (bool) $yt->intermission_enabled,
                    'intermission_duration' => max(5, (int) $yt->intermission_duration),
                    'mode' => $yt->playback_mode,
                ],
                'sources' => $sources,
            ],
            'weather' => $display->weather_enabled ? $this->weather->current() : null,
        ];

        $payload['version'] = md5(json_encode($payload));
        $payload['server_time'] = $now->toIso8601String();

        return $payload;
    }
}
