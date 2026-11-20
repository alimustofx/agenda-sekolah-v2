<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $guarded = [];




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
