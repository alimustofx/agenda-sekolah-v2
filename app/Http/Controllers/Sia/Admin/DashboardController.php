<?php

namespace App\Http\Controllers\Sia\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgendaDay;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\SchoolActivity;
use App\Models\YoutubeSource;
use App\Support\SiaTime;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $now = SiaTime::now();
        $todayStr = $now->toDateString();

        $todayAgenda = AgendaDay::query()->whereDate('agenda_date', $todayStr)->whereIn('status', ['draft', 'published'])
            ->with('items.category')->orderByRaw("CASE status WHEN 'published' THEN 0 ELSE 1 END")->latest('updated_at')->first();

        $nowHm = $now->format('H:i');
        $items = $todayAgenda ? $todayAgenda->items->map(function ($i) use ($nowHm) {
            $start = substr((string) $i->start_time, 0, 5) ?: null;
            $end = substr((string) $i->end_time, 0, 5) ?: null;
            $ongoing = $start && $start <= $nowHm && ($end ? $end >= $nowHm : true);

            return ['id' => $i->id, 'title' => $i->title, 'time_label' => $i->timeLabel(), 'ongoing' => (bool) $ongoing];
        })->values() : collect();

        $logs = AuditLog::query()->with('user:id,name')->latest('id')->limit(6)->get()->map(fn ($l) => [
            'id' => $l->id, 'description' => $l->description, 'user' => $l->user?->name ?? 'Sistem',
            'time' => $l->created_at->setTimezone(SiaTime::tz())->format('d/m H.i'),
        ]);

        return Inertia::render('Sia/Admin/Dashboard', [
            'todayLabel' => SiaTime::longDate($now),
            'todayAgenda' => $todayAgenda ? [
                'id' => $todayAgenda->id, 'title' => $todayAgenda->title, 'status' => $todayAgenda->status,
                'items_count' => $items->count(), 'ongoing_count' => $items->where('ongoing', true)->count(),
            ] : null,
            'items' => $items,
            'overview' => [
                'upcoming' => AgendaDay::query()->where('status', 'published')->whereDate('agenda_date', '>', $todayStr)->count(),
                'drafts' => AgendaDay::query()->where('status', 'draft')->count(),
                'announcements' => Announcement::query()->active()->count(),
                'videos' => YoutubeSource::query()->where('is_active', true)->count(),
                'ongoing_activities' => SchoolActivity::query()->get()->filter(fn ($a) => $a->effectiveStatus() === 'ongoing')->count(),
            ],
            'logs' => $logs,
        ]);
    }
}
