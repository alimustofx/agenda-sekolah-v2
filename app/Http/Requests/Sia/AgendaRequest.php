<?php

namespace App\Http\Requests\Sia;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $time = ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'];

        return [
            'title' => ['required', 'string', 'max:200'],
            'agenda_date' => ['required', 'date'],
            'introduction' => ['nullable', 'string', 'max:5000'],
            'recipients' => ['nullable', 'string', 'max:2000'],
            'uniform_information' => ['nullable', 'string', 'max:1000'],
            'general_notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:draft,published,archived'],
            'show_on_website' => ['boolean'],
            'show_on_tv' => ['boolean'],
            'items' => ['array', 'max:100'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.title' => ['required', 'string', 'max:200'],
            'items.*.category_id' => ['nullable', 'exists:agenda_categories,id'],
            'items.*.start_time' => $time,
            'items.*.end_time' => $time,
            'items.*.time_description' => ['nullable', 'string', 'max:120'],
            'items.*.location' => ['nullable', 'string', 'max:200'],
            'items.*.description' => ['nullable', 'string', 'max:3000'],
            'items.*.responsible_label' => ['nullable', 'string', 'max:60'],
            'items.*.responsible_person' => ['nullable', 'string', 'max:200'],
            'items.*.participants' => ['nullable', 'string', 'max:2000'],
            'items.*.officers' => ['nullable', 'string', 'max:2000'],
            'items.*.additional_notes' => ['nullable', 'string', 'max:3000'],
            'items.*.priority' => ['nullable', 'in:normal,important,urgent'],
            'items.*.schedules' => ['nullable', 'array', 'max:10'],
            'items.*.schedules.*.start_time' => $time,
            'items.*.schedules.*.end_time' => $time,
            'items.*.schedules.*.time_description' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul agenda', 'agenda_date' => 'tanggal', 'items.*.title' => 'judul kegiatan',
            'items.*.start_time' => 'waktu mulai', 'items.*.end_time' => 'waktu selesai',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $v) {
                if ($this->input('status') === 'published' && count($this->input('items', [])) === 0) {
                    $v->errors()->add('items', 'Tambahkan minimal satu kegiatan sebelum menerbitkan agenda.');
                }
            },
        ];
    }
}
