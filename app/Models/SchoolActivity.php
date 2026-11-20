<?php

namespace App\Models;

use App\Support\SiaTime;
use Illuminate\Database\Eloquent\Model;

class SchoolActivity extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_status_manual' => 'boolean',
            'show_on_website' => 'boolean',
            'show_on_tv' => 'boolean',
        ];
    }

    /** Status efektif: otomatis dari waktu, kecuali admin menetapkan manual. */
    public function effectiveStatus(): string
    {
        if ($this->is_status_manual) {
            return $this->status;
        }

        $start = SiaTime::parse($this->getRawOriginal('start_at'));
        if (! $start) {
            return $this->status ?: 'scheduled';
        }
        $end = SiaTime::parse($this->getRawOriginal('end_at')) ?: $start->copy()->endOfDay();
        $now = SiaTime::now();

        if ($now->lt($start)) {
            return 'scheduled';
        }

        return $now->lte($end) ? 'ongoing' : 'completed';
    }

    public function timeLabel(): string
    {
        $start = SiaTime::parse($this->getRawOriginal('start_at'));
        $end = SiaTime::parse($this->getRawOriginal('end_at'));
        if (! $start) {
            return $this->time_description ?: '';
        }

        $label = $start->format('H.i') . ' WIB';
        if ($end) {
            $sameDay = $end->isSameDay($start);
            $label .= ' – ' . ($sameDay ? $end->format('H.i') . ' WIB' : SiaTime::shortDate($end) . ', ' . $end->format('H.i') . ' WIB');
        } else {
            $label .= ' – ' . ($this->time_description ?: 'Selesai');
        }

        return $label;
    }

    public function toPublic(): array
    {
        $start = SiaTime::parse($this->getRawOriginal('start_at'));

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'responsible_person' => $this->responsible_person,
            'time_label' => $this->timeLabel(),
            'date' => $start ? SiaTime::longDate($start) : null,
            'status' => $this->effectiveStatus(),
        ];
    }

    public function toForm(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'responsible_person' => $this->responsible_person,
            'start_at' => SiaTime::dt($this->getRawOriginal('start_at')),
            'end_at' => SiaTime::dt($this->getRawOriginal('end_at')),
            'time_description' => $this->time_description,
            'status' => $this->status,
            'is_status_manual' => $this->is_status_manual,
            'effective_status' => $this->effectiveStatus(),
            'show_on_website' => $this->show_on_website,
            'show_on_tv' => $this->show_on_tv,
        ];
    }
}
