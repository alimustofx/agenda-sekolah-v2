<?php

namespace App\Services\Parser;

use App\Models\AgendaCategory;

class AgendaParserManager
{
    public function parse(string $text): array
    {
        $categories = AgendaCategory::query()->where('is_active', true)->pluck('id', 'slug')->all();
        $result = (new WhatsAppAgendaParser($categories))->parse($text);
        $result['parser_type'] = 'rule';

        if (config('sia.ai_parser.enabled') && $this->needsRefinement($result)) {
            try {
                $refiner = app(config('sia.ai_parser.class'));
                $result = $refiner->refine($text, $result);
                $result['summary'] = WhatsAppAgendaParser::summarize($result);
                $result['parser_type'] = 'hybrid';
            } catch (\Throwable $e) {
                report($e); // AI tidak boleh menjadi ketergantungan seluruh sistem.
            }
        }

        return $result;
    }

    private function needsRefinement(array $result): bool
    {
        $s = $result['summary'] ?? [];

        return ($s['missing'] ?? 0) + ($s['invalid'] ?? 0) + ($s['warn'] ?? 0) > 0;
    }
}
