<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParserLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['parsing_result' => 'array', 'confidence_summary' => 'array'];
    }
}
