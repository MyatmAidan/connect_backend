<?php

namespace App\Http\Requests\Api\V1\Admin\Event;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'section' => ['sometimes', 'required', 'string', 'max:255'],
            'event_date' => ['sometimes', 'required', 'date'],
            'meeting_url' => ['nullable', 'string', 'url', 'max:500'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
