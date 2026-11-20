<?php

namespace App\Models;

use App\Support\SiaTime;
use Illuminate\Database\Eloquent\Model;

class RunningText extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive($query)
    {
        $now = SiaTime::now()->toDateTimeString();

        return $query->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('start_at')->orWhere('start_at', '<=', $now))
            ->where(fn ($w) => $w->whereNull('end_at')->orWhere('end_at', '>=', $now));
    }

    public function toForm(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'speed' => $this->speed,
            'text_color' => $this->text_color,
            'background_color' => $this->background_color,
            'priority' => $this->priority,
            'start_at' => SiaTime::dt($this->getRawOriginal('start_at')),
            'end_at' => SiaTime::dt($this->getRawOriginal('end_at')),
            'display_order' => $this->display_order,
            'is_active' => $this->is_active,
        ];
    }
}
