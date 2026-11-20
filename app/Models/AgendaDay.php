<?php

namespace App\Models;

use App\Support\SiaTime;
use Illuminate\Database\Eloquent\Model;

class AgendaDay extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'agenda_date' => 'date',
            'published_at' => 'datetime',
            'show_on_website' => 'boolean',
            'show_on_tv' => 'boolean',
        ];
    }

    public function items()
    {
        return $this->hasMany(AgendaItem::class)->orderBy('display_order')->orderBy('id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublishedFor($query, string $channel)
    {
        return $query->where('status', 'published')
            ->where($channel === 'tv' ? 'show_on_tv' : 'show_on_website', true);
    }

    /** Ringkasan untuk daftar (tanpa item). */
    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'date' => $this->agenda_date->format('Y-m-d'),
            'day_name' => SiaTime::dayName($this->agenda_date),
            'long_date' => SiaTime::longDate($this->agenda_date),
            'short_date' => SiaTime::shortDate($this->agenda_date),
            'status' => $this->status,
            'items_count' => $this->items_count ?? $this->items->count(),
            'show_on_website' => $this->show_on_website,
            'show_on_tv' => $this->show_on_tv,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i'),
        ];
    }

    /** Detail lengkap untuk halaman publik dan TV. */
    public function toPublic(): array
    {
        return $this->toSummary() + [
            'introduction' => $this->introduction,
            'recipients' => $this->recipients,
            'uniform_information' => $this->uniform_information,
            'general_notes' => $this->general_notes,
            'items' => $this->items->map->toPublic()->values()->all(),
        ];
    }
}
