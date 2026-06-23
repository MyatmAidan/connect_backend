<?php

namespace App\Http\Requests\Api\V1\Admin\Event;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'meeting_url' => ['nullable', 'string', 'url', 'max:500'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
