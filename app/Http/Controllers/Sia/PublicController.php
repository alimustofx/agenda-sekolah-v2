<?php

namespace App\Http\Controllers\Sia;

use App\Http\Controllers\Controller;
use App\Models\AgendaCategory;
use App\Models\AgendaDay;
use App\Models\Announcement;
use App\Models\SchoolActivity;
use App\Support\SiaTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class PublicController extends Controller
{
    public function home()
    {
        $today = SiaTime::today();

        $agenda = AgendaDay::query()->publishedFor('website')
            ->whereDate('agenda_date', $today->toDateString())
            ->with(['items.category', 'items.schedules'])->latest('updated_at')->first();

        $upcoming = AgendaDay::query()->publishedFor('website')
            ->whereDate('agenda_date', '>', $today->toDateString())
            ->withCount('items')->orderBy('agenda_date')->limit(5)->get()->map->toSummary();

        $announcements = Announcement::query()->active('website')->byPriority()->limit(3)->get()->map->toPublic();

        $activities = SchoolActivity::query()->where('show_on_website', true)->get()
            ->filter(fn ($a) => $a->effectiveStatus() === 'ongoing')->map->toPublic()->values();

        return Inertia::render('Sia/Public/Home', [
            'todayLabel' => SiaTime::longDate($today),
            'agenda' => $agenda?->toPublic(),
            'upcoming' => $upcoming,
            'announcements' => $announcements,
            'activities' => $activities,
        ]);
    }

    public function agendaIndex(Request $request)
    {
        $today = SiaTime::today()->toDateString();
        $scope = in_array($request->query('scope'), ['mendatang', 'lalu', 'semua']) ? $request->query('scope') : 'mendatang';
        $q = trim((string) $request->query('q', ''));
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date')) ? $request->query('date') : null;
        $category = $request->query('category');

        $query = AgendaDay::query()->publishedFor('website')->withCount('items');

        if ($scope === 'mendatang') {
            $query->whereDate('agenda_date', '>=', $today)->orderBy('agenda_date');
        } elseif ($scope === 'lalu') {
            $query->whereDate('agenda_date', '<', $today)->orderByDesc('agenda_date');
        } else {
            $query->orderByDesc('agenda_date');
        }
        if ($date) {
            $query->whereDate('agenda_date', $date);
        }
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('title', 'like', "%{$q}%")
                ->orWhereHas('items', fn ($i) => $i->where('title', 'like', "%{$q}%")));
        }
        if ($category) {
            $query->whereHas('items', fn ($i) => $i->where('category_id', $category));
        }

        $paginator = $query->paginate(10)->withQueryString();

        return Inertia::render('Sia/Public/Agenda/Index', [
            'agendas' => $paginator->through(fn ($d) => $d->toSummary()),
            'filters' => ['q' => $q, 'date' => $date, 'category' => $category, 'scope' => $scope],
            'categories' => AgendaCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function agendaShow(AgendaDay $agenda)
    {
        abort_unless($agenda->status === 'published' && $agenda->show_on_website, 404);
        $agenda->load(['items.category', 'items.schedules']);

        return Inertia::render('Sia/Public/Agenda/Show', ['agenda' => $agenda->toPublic()]);
    }

    public function calendar(Request $request)
    {
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('bulan')) ? $request->query('bulan') : SiaTime::today()->format('Y-m');
        $first = Carbon::createFromFormat('Y-m-d', $month . '-01', SiaTime::tz())->startOfDay();
        $last = $first->copy()->endOfMonth();

        $days = AgendaDay::query()->publishedFor('website')
            ->whereBetween('agenda_date', [$first->toDateString(), $last->toDateString()])
            ->withCount('items')->orderBy('agenda_date')->get();

        $byDate = [];
        foreach ($days as $d) {
            $byDate[$d->agenda_date->format('Y-m-d')][] = ['id' => $d->id, 'title' => $d->title, 'items_count' => $d->items_count];
        }

        return Inertia::render('Sia/Public/Agenda/Calendar', [
            'month' => $first->format('Y-m'),
            'monthLabel' => SiaTime::MONTHS[$first->month] . ' ' . $first->year,
            'prev' => $first->copy()->subMonth()->format('Y-m'),
            'next' => $first->copy()->addMonth()->format('Y-m'),
            'startOffset' => $first->dayOfWeekIso - 1,
            'daysInMonth' => $first->daysInMonth,
            'today' => SiaTime::today()->format('Y-m-d'),
            'byDate' => (object) $byDate,
        ]);
    }

    public function announcements(Request $request)
    {
        $archive = $request->query('arsip') === '1';
        $query = Announcement::query()->where('show_on_website', true);

        if ($archive) {
            $query->archived()->orderByDesc('end_at')->orderByDesc('id');
        } else {
            $query->active('website')->byPriority();
        }

        return Inertia::render('Sia/Public/Announcements/Index', [
            'announcements' => $query->paginate(10)->withQueryString()->through(fn ($a) => $a->toPublic()),
            'archive' => $archive,
        ]);
    }

    public function announcementShow(Announcement $announcement)
    {
        $started = ! $announcement->getRawOriginal('start_at') || $announcement->getRawOriginal('start_at') <= SiaTime::now()->toDateTimeString();
        abort_unless($announcement->show_on_website && $announcement->is_active && $started, 404);

        return Inertia::render('Sia/Public/Announcements/Show', [
            'announcement' => $announcement->toPublic() + ['state' => $announcement->stateLabel()],
        ]);
    }

    public function activities()
    {
        $all = SchoolActivity::query()->where('show_on_website', true)->orderByDesc('start_at')->limit(60)->get();
        $group = fn (string $status) => $all->filter(fn ($a) => $a->effectiveStatus() === $status)->map->toPublic()->values();

        return Inertia::render('Sia/Public/Activities/Index', [
            'ongoing' => $group('ongoing'),
            'scheduled' => $group('scheduled'),
            'completed' => $group('completed')->take(10)->values(),
        ]);
    }
}
