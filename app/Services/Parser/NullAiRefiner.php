<?php

namespace App\Services\Parser;

class NullAiRefiner implements AgendaRefiner
{
    public function refine(string $originalText, array $ruleResult): array
    {
        return $ruleResult;
    }
}
