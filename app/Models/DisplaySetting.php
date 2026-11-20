<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisplaySetting extends Model
{
    protected $guarded = [];


    protected function casts(): array
    {
        return [
            'adaptive_pagination' => 'boolean', 'video_panel_enabled' => 'boolean', 'information_panel_enabled' => 'boolean',
            'weather_enabled' => 'boolean', 'running_text_enabled' => 'boolean', 'panel_modes' => 'array',
        ];
    }


    /** Pengaturan hanya memiliki satu baris; dibuat otomatis jika belum ada. */
    public static function current(): self
    {
        return static::query()->first() ?: static::query()->create(static::defaults())->fresh();
    }

    protected static function defaults(): array
    {
        return ['panel_modes' => ['announcement', 'activity', 'school_info', 'weather']];
    }
}
