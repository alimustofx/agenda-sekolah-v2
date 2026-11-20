<?php

namespace App\Services\Sia;

use App\Models\AgendaDay;
use App\Models\AgendaItem;
use App\Support\SiaTime;
use Illuminate\Support\Facades\DB;

class AgendaService
{
    private const ITEM_FIELDS = [
        'category_id', 'title', 'description', 'time_description', 'location', 'responsible_label',
        'responsible_person', 'participants', 'officers', 'additional_notes', 'priority',
    ];

    public function save(?AgendaDay $day, array $data, int $userId): AgendaDay
    {
        return DB::transaction(function () use ($day, $data, $userId) {
            $attributes = [
                'title' => $data['title'],
                'agenda_date' => $data['agenda_date'],
                'introduction' => $data['introduction'] ?? null,
                'recipients' => $data['recipients'] ?? null,
                'uniform_information' => $data['uniform_information'] ?? null,
                'general_notes' => $data['general_notes'] ?? null,
                'status' => $data['status'],
                'show_on_website' => (bool) ($data['show_on_website'] ?? true),
                'show_on_tv' => (bool) ($data['show_on_tv'] ?? true),
                'updated_by' => $userId,
            ];
            if (isset($data['original_source_text'])) {
                $attributes['original_source_text'] = $data['original_source_text'];
            }

            if ($day) {
                $attributes['published_at'] = $attributes['status'] === 'published' ? ($day->published_at ?: now()) : null;
                $day->update($attributes);
            } else {
                $attributes['created_by'] = $userId;
                $attributes['published_at'] = $attributes['status'] === 'published' ? now() : null;
                $day = AgendaDay::create($attributes);
            }

            $this->syncItems($day, $data['items'] ?? []);

            return $day->fresh();
        });
    }

    private function syncItems(AgendaDay $day, array $items): void
    {
        $keep = [];
        foreach (array_values($items) as $order => $row) {
            $payload = ['display_order' => $order + 1,
                'start_time' => SiaTime::hm($row['start_time'] ?? null),
                'end_time' => SiaTime::hm($row['end_time'] ?? null),
            ];
            foreach (self::ITEM_FIELDS as $field) {
                $payload[$field] = $row[$field] ?? null;
            }
            $payload['priority'] = $payload['priority'] ?: 'normal';

            $item = null;
            if (! empty($row['id'])) {
                $item = $day->items()->whereKey($row['id'])->first();
            }
            if ($item) {
                $item->update($payload);
            } else {
                $item = $day->items()->create($payload);
            }
            $keep[] = $item->id;

            $item->schedules()->delete();
            foreach ($row['schedules'] ?? [] as $s) {
                if (empty($s['start_time']) && empty($s['time_description'])) {
                    continue;
                }
                $item->schedules()->create([
                    'start_time' => SiaTime::hm($s['start_time'] ?? null),
                    'end_time' => SiaTime::hm($s['end_time'] ?? null),
                    'time_description' => $s['time_description'] ?? null,
                ]);
            }
        }

        $day->items()->whereNotIn('id', $keep)->delete();
    }

    public function duplicate(AgendaDay $source, int $userId): AgendaDay
    {
        return DB::transaction(function () use ($source, $userId) {
            $copy = AgendaDay::create([
                'title' => $source->title,
                'agenda_date' => SiaTime::today()->addDay()->format('Y-m-d'),
                'introduction' => $source->introduction,
                'recipients' => $source->recipients,
                'uniform_information' => $source->uniform_information,
                'general_notes' => $source->general_notes,
                'status' => 'draft',
                'show_on_website' => $source->show_on_website,
                'show_on_tv' => $source->show_on_tv,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            foreach ($source->items()->with('schedules')->get() as $item) {
                $clone = $copy->items()->create($item->only(array_merge(self::ITEM_FIELDS, ['start_time', 'end_time', 'display_order'])));
                foreach ($item->schedules as $s) {
                    $clone->schedules()->create($s->only(['start_time', 'end_time', 'time_description']));
                }
            }

            return $copy;
        });
    }

    public function toForm(AgendaDay $day): array
    {
        $day->loadMissing('items.schedules');

        return [
            'id' => $day->id,
            'title' => $day->title,
            'agenda_date' => $day->agenda_date->format('Y-m-d'),
            'introduction' => $day->introduction,
            'recipients' => $day->recipients,
            'uniform_information' => $day->uniform_information,
            'general_notes' => $day->general_notes,
            'status' => $day->status,
            'show_on_website' => $day->show_on_website,
            'show_on_tv' => $day->show_on_tv,
            'original_source_text' => $day->original_source_text,
            'items' => $day->items->map(fn (AgendaItem $i) => $i->toForm())->values()->all(),
        ];
    }
}
