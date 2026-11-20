<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class SiaTime
{
    public const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public const MONTHS = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

    public static function tz(): string
    {
        return config('sia.timezone', 'Asia/Jakarta');
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::tz());
    }

    public static function today(): Carbon
    {
        return self::now()->startOfDay();
    }

    public static function dayName(CarbonInterface $d): string
    {
        return self::DAYS[$d->dayOfWeekIso];
    }

    public static function longDate(CarbonInterface $d): string
    {
        return self::dayName($d) . ', ' . $d->day . ' ' . self::MONTHS[$d->month] . ' ' . $d->year;
    }

    public static function shortDate(CarbonInterface $d): string
    {
        return $d->day . ' ' . mb_substr(self::MONTHS[$d->month], 0, 3) . ' ' . $d->year;
    }

    /** Normalisasi nilai waktu database/form menjadi "HH:MM" atau null. */
    public static function hm(?string $time): ?string
    {
        if ($time === null || trim($time) === '') {
            return null;
        }

        return substr(trim($time), 0, 5);
    }

    /** Parse nilai datetime "naif" dari database sebagai waktu setempat. */
    public static function parse(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value, self::tz());
    }

    public static function dt(?string $raw): ?string
    {
        return $raw ? substr(str_replace(' ', 'T', $raw), 0, 16) : null;
    }
}
