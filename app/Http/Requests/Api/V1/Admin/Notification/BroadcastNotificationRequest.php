<?php

namespace App\Http\Requests\Api\V1\Admin\Notification;

use Illuminate\Foundation\Http\FormRequest;

class BroadcastNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'channel' => ['required', 'string', 'in:in_app,telegram,push'],
            'user_ids' => ['sometimes', 'array', 'min:1'],
            'user_ids.*' => ['required', 'string', 'exists:users,id'],
        ];
    }
}
