<?php

namespace App\Services\Sia;

use App\Models\WeatherSetting;
use App\Support\SiaTime;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    private const CODES = [
        0 => 'Cerah', 1 => 'Cerah berawan', 2 => 'Berawan sebagian', 3 => 'Berawan', 45 => 'Berkabut', 48 => 'Berkabut',
        51 => 'Gerimis ringan', 53 => 'Gerimis', 55 => 'Gerimis lebat', 56 => 'Gerimis dingin', 57 => 'Gerimis dingin',
        61 => 'Hujan ringan', 63 => 'Hujan sedang', 65 => 'Hujan lebat', 66 => 'Hujan dingin', 67 => 'Hujan dingin',
        71 => 'Salju ringan', 73 => 'Salju', 75 => 'Salju lebat', 77 => 'Butiran salju',
        80 => 'Hujan lokal ringan', 81 => 'Hujan lokal', 82 => 'Hujan lokal lebat', 85 => 'Hujan salju', 86 => 'Hujan salju lebat',
        95 => 'Badai petir', 96 => 'Badai petir disertai hujan es', 99 => 'Badai petir disertai hujan es',
    ];

    /** Cuaca terkini (di-cache). Mengembalikan null bila nonaktif atau belum pernah berhasil diambil. */
    public function current(): ?array
    {
        $setting = WeatherSetting::current();
        if (! $setting->is_enabled) {
            return null;
        }

        $key = 'sia.weather.' . md5($setting->latitude . ',' . $setting->longitude);
        if ($cached = Cache::get($key)) {
            return $cached;
        }
        if (Cache::get($key . '.fail')) {
            return Cache::get($key . '.stale');
        }

        try {
            $res = Http::timeout(6)->get(config('sia.weather.endpoint'), [
                'latitude' => $setting->latitude,
                'longitude' => $setting->longitude,
                'current' => 'temperature_2m,relative_humidity_2m,weather_code',
                'timezone' => SiaTime::tz(),
            ]);
            if (! $res->ok() || $res->json('current.temperature_2m') === null) {
                throw new \RuntimeException('Respons cuaca tidak valid.');
            }

            $code = (int) $res->json('current.weather_code');
            $data = [
                'location' => $setting->location,
                'temperature' => (int) round($res->json('current.temperature_2m')),
                'humidity' => $res->json('current.relative_humidity_2m'),
                'condition' => self::CODES[$code] ?? 'Tidak diketahui',
                'code' => $code,
                'updated_at' => SiaTime::now()->format('H.i') . ' WIB',
            ];
            Cache::put($key, $data, max(5, (int) $setting->cache_duration) * 60);
            Cache::forever($key . '.stale', $data);

            return $data;
        } catch (\Throwable $e) {
            report($e);
            Cache::put($key . '.fail', 1, 120); // jangan menekan API setiap polling TV.

            return Cache::get($key . '.stale');
        }
    }
}
