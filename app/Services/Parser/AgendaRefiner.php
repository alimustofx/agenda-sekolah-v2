<?php

namespace App\Services\Parser;

/**
 * Kontrak lapisan 2 (AI-assisted). Implementasi boleh memperbaiki hasil rule-based,
 * tetapi TIDAK boleh mengarang informasi yang tidak ada pada teks sumber dan
 * harus menandai field yang meragukan lewat 'statuses' / '_status'.
 */
interface AgendaRefiner
{
    public function refine(string $originalText, array $ruleResult): array;
}
