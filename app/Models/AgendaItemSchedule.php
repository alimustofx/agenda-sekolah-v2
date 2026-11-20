<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgendaItemSchedule extends Model
{
    protected $guarded = [];

    public function item()
    {
        return $this->belongsTo(AgendaItem::class, 'agenda_item_id');
    }
}
