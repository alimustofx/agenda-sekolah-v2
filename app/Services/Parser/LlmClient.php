<?php

namespace App\Services\Parser;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/** Klien tipis untuk tiga penyedia LLM. Kunci API hanya dibaca dari .env di server. */
class LlmClient
{
    private const DEFAULT_MODELS = [
        'anthropic' => 'claude-haiku-5-5',
        'openai' => 'gpt-4o-mini',
        'gemini' => 'gemini-2.0-flash',
    ];

    public function driver(): string
    {
        return strtolower((string) config('sia.ai_parser.driver', 'anthropic'));
    }

    public function model(): string
    {
        return config('sia.ai_parser.model') ?: (self::DEFAULT_MODELS[$this->driver()] ?? '');
    }

    public function configured(): bool
    {
        return (bool) config('sia.ai_parser.key') && isset(self::DEFAULT_MODELS[$this->driver()]);
    }

    public function complete(string $system, string $user, bool $json = true): string
    {
        $key = config('sia.ai_parser.key');
        if (! $key) {
            throw new RuntimeException('SIA_AI_KEY belum diisi di .env.');
        }
        $driver = $this->driver();
        $model = $this->model();
        $http = Http::timeout(max(10, (int) config('sia.ai_parser.timeout', 90)))->connectTimeout(10)->acceptJson();

        switch ($driver) {
            case 'anthropic':
                $res = $http->withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01'])
                    ->post('https://api.anthropic.com/v1/messages', [
                        'model' => $model, 'max_tokens' => 4096, 'system' => $system,
                        'messages' => [['role' => 'user', 'content' => $user]],
                    ]);
                $text = collect($res->json('content', []))->where('type', 'text')->pluck('text')->implode('');
                break;

            case 'openai':
                $payload = [
                    'model' => $model, 'temperature' => 0,
                    'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $user]],
                ];
                if ($json) {
                    $payload['response_format'] = ['type' => 'json_object'];
                }
                $res = $http->withToken($key)->post('https://api.openai.com/v1/chat/completions', $payload);
                $text = (string) $res->json('choices.0.message.content', '');
                break;

            case 'gemini':
                $config = ['temperature' => 0];
                if ($json) {
                    $config['responseMimeType'] = 'application/json';
                }
                $res = $http->withHeaders(['x-goog-api-key' => $key])
                    ->post('https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent', [
                        'systemInstruction' => ['parts' => [['text' => $system]]],
                        'contents' => [['role' => 'user', 'parts' => [['text' => $user]]]],
                        'generationConfig' => $config,
                    ]);
                $text = collect($res->json('candidates.0.content.parts', []))->pluck('text')->implode('');
                break;

            default:
                throw new RuntimeException("Penyedia AI \"{$driver}\" tidak dikenal. Gunakan anthropic, openai, atau gemini.");
        }

        if (! $res->successful()) {
            $why = $res->json('error.message');
            throw new RuntimeException(ucfirst($driver) . ' menolak permintaan (HTTP ' . $res->status() . ')' . (is_string($why) ? ': ' . Str::limit($why, 160) : '.'));
        }
        if (trim($text) === '') {
            throw new RuntimeException('Respons AI kosong.');
        }

        return $text;
    }
}
