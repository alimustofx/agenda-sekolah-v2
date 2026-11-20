<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YoutubeSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['playback_enabled' => 'boolean', 'autoplay' => 'boolean', 'muted' => 'boolean', 'shuffle' => 'boolean', 'loop' => 'boolean', 'intermission_enabled' => 'boolean'];
    }



    /** Pengaturan hanya memiliki satu baris; dibuat otomatis jika belum ada. */
    public static function current(): self
    {
        return static::query()->first() ?: static::query()->create(static::defaults())->fresh();
    }

    protected static function defaults(): array
    {
        return [];
    }
}
