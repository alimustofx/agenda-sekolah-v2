<?php

namespace App\Models;

use App\Support\SiaTime;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'show_on_website' => 'boolean',
            'show_on_tv' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public const PRIORITY_ORDER = "CASE priority WHEN 'urgent' THEN 0 WHEN 'important' THEN 1 ELSE 2 END";

    public function scopeActive($query, ?string $channel = null)
    {
        $now = SiaTime::now()->toDateTimeString();
        $query->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('start_at')->orWhere('start_at', '<=', $now))
            ->where(fn ($w) => $w->whereNull('end_at')->orWhere('end_at', '>=', $now));

        if ($channel === 'tv') {
            $query->where('show_on_tv', true);
        } elseif ($channel === 'website') {
            $query->where('show_on_website', true);
        }

        return $query;
    }

    public function scopeExpired($query)
    {
        $now = SiaTime::now()->toDateTimeString();

        return $query->where(fn ($w) => $w->where('is_active', false)->orWhere('end_at', '<', $now));
    }

    public function scopeByPriority($query)
    {
        return $query->orderByRaw(self::PRIORITY_ORDER)->orderByDesc('start_at')->orderByDesc('id');
    }

    public function stateLabel(): string
    {
        $now = SiaTime::now()->toDateTimeString();
        $start = $this->getRawOriginal('start_at');
        $end = $this->getRawOriginal('end_at');

        if (! $this->is_active) {
            return 'nonaktif';
        }
        if ($end && $end < $now) {
            return 'berakhir';
        }
        if ($start && $start > $now) {
            return 'terjadwal';
        }

        return 'aktif';
    }

    public function toPublic(): array
    {
        $start = $this->getRawOriginal('start_at');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'category' => $this->category,
            'priority' => $this->priority,
            'date' => $start ? SiaTime::longDate(SiaTime::parse($start)) : SiaTime::longDate($this->created_at->copy()->setTimezone(SiaTime::tz())),
            'start_at' => SiaTime::dt($start),
            'end_at' => SiaTime::dt($this->getRawOriginal('end_at')),
        ];
    }

    public function toForm(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'category' => $this->category,
            'priority' => $this->priority,
            'start_at' => SiaTime::dt($this->getRawOriginal('start_at')),
            'end_at' => SiaTime::dt($this->getRawOriginal('end_at')),
            'show_on_website' => $this->show_on_website,
            'show_on_tv' => $this->show_on_tv,
            'is_active' => $this->is_active,
            'state' => $this->stateLabel(),
        ];
    }
}
