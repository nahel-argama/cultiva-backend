<?php

namespace Cultiva\Integrations\GoLog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateGoLogWorkScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'schedule_date' => ['required', 'date', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }
}
