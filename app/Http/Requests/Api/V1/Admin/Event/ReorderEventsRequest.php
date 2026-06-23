<?php

namespace App\Http\Requests\Api\V1\Admin\Event;

use Illuminate\Foundation\Http\FormRequest;

class ReorderEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'ulid', 'exists:events,id'],
        ];
    }
}
