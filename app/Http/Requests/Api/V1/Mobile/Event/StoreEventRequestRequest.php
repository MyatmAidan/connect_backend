<?php

namespace App\Http\Requests\Api\V1\Mobile\Event;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequestRequest extends FormRequest
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
            'message' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
