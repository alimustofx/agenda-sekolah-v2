<?php

return [
    // Seluruh logika "hari ini", jam, dan jadwal memakai zona waktu ini.
    'timezone' => env('SIA_TIMEZONE', 'Asia/Jakarta'),

    // Lapisan 2 parser (AI). Dimatikan pada versi ini. Untuk mengaktifkan nanti:
    // buat class yang mengimplementasikan App\Services\Parser\AgendaRefiner,
    // daftarkan di 'class', lalu set SIA_AI_PARSER=true.
    'ai_parser' => [
        'enabled' => env('SIA_AI_PARSER', false),
        'class' => \App\Services\Parser\NullAiRefiner::class,
    ],

    'youtube' => [
        // Opsional. Tanpa API key, playlist dibaca langsung oleh pemutar di TV.
        'api_key' => env('YOUTUBE_API_KEY'),
    ],

    'weather' => [
        'endpoint' => 'https://api.open-meteo.com/v1/forecast',
    ],
];
