<?php

namespace App\Models;

use App\Support\SiaTime;
use Illuminate\Database\Eloquent\Model;

class AgendaItem extends Model
{
    protected $guarded = [];

    public function day()
    {
        return $this->belongsTo(AgendaDay::class, 'agenda_day_id');
    }

    public function category()
    {
        return $this->belongsTo(AgendaCategory::class, 'category_id');
    }

    public function schedules()
    {
        return $this->hasMany(AgendaItemSchedule::class)->orderBy('start_time');
    }

    public static function formatTime(?string $start, ?string $end, ?string $desc): string
    {
        $dot = fn (?string $v) => $v ? str_replace(':', '.', SiaTime::hm($v)) : null;
        $s = $dot($start);
        $e = $dot($end);

        if (! $s) {
            return $desc ?: 'Sepanjang hari';
        }
        if ($e) {
            return $s . ' – ' . $e . ($desc ? ' (' . $desc . ')' : '');
        }

        return $s . ' – ' . ($desc ?: 'Selesai');
    }

    public function timeLabel(): string
    {
        $label = self::formatTime($this->start_time, $this->end_time, $this->time_description);
        foreach ($this->schedules as $schedule) {
            $label .= ', ' . self::formatTime($schedule->start_time, $schedule->end_time, $schedule->time_description);
        }

        return $label;
    }

    public function toPublic(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category ? ['id' => $this->category->id, 'name' => $this->category->name, 'color' => $this->category->color] : null,
            'start_time' => SiaTime::hm($this->start_time),
            'end_time' => SiaTime::hm($this->end_time),
            'time_description' => $this->time_description,
            'time_label' => $this->timeLabel(),
            'extra_times' => $this->schedules->map(fn ($s) => self::formatTime($s->start_time, $s->end_time, $s->time_description))->values()->all(),
            'location' => $this->location,
            'description' => $this->description,
            'responsible_label' => $this->responsible_label,
            'responsible_person' => $this->responsible_person,
            'participants' => $this->participants,
            'officers' => $this->officers,
            'additional_notes' => $this->additional_notes,
            'priority' => $this->priority,
        ];
    }

    /** Bentuk mentah untuk form editor admin. */
    public function toForm(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category_id' => $this->category_id,
            'start_time' => SiaTime::hm($this->start_time),
            'end_time' => SiaTime::hm($this->end_time),
            'time_description' => $this->time_description,
            'location' => $this->location,
            'description' => $this->description,
            'responsible_label' => $this->responsible_label,
            'responsible_person' => $this->responsible_person,
            'participants' => $this->participants,
            'officers' => $this->officers,
            'additional_notes' => $this->additional_notes,
            'priority' => $this->priority,
            'schedules' => $this->schedules->map(fn ($s) => [
                'start_time' => SiaTime::hm($s->start_time),
                'end_time' => SiaTime::hm($s->end_time),
                'time_description' => $s->time_description,
            ])->values()->all(),
        ];
    }
}
