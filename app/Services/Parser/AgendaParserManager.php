<?php

namespace App\Services\Parser;

use App\Models\AgendaCategory;

class AgendaParserManager
{
    /** $allowAi: izin admin untuk mengirim teks ke penyedia AI pada pemrosesan ini. */
    public function parse(string $text, bool $allowAi = true): array
    {
        $categories = AgendaCategory::query()->where('is_active', true)->pluck('id', 'slug')->all();
        $result = (new WhatsAppAgendaParser($categories))->parse($text);
        $result['parser_type'] = 'rule';

        if ($allowAi && config('sia.ai_parser.enabled') && $this->needsRefinement($result)) {
            try {
                $refined = app(config('sia.ai_parser.class'))->refine($text, $result);
                $refined['summary'] = WhatsAppAgendaParser::summarize($refined);
                $refined['parser_type'] = 'hybrid';
                $result = $refined;
            } catch (\Throwable $e) {
                report($e);
                // AI tidak boleh menjadi ketergantungan: hasil parser aturan tetap dipakai.
                $result['ai_error'] = mb_substr($e->getMessage(), 0, 200);
            }
        }

        return $result;
    }

    /** AI dipanggil hanya bila ada ketidakpastian pada tanggal, judul, atau waktu. */
    private function needsRefinement(array $result): bool
    {
        if (empty($result['items'])) {
            return true;
        }
        if (($result['statuses']['agenda_date']['state'] ?? 'ok') !== 'ok') {
            return true;
        }
        foreach ($result['items'] as $item) {
            foreach (['title', 'time', 'end_time'] as $key) {
                if (($item['_status'][$key]['state'] ?? 'ok') !== 'ok') {
                    return true;
                }
            }
        }

        return false;
    }
}
