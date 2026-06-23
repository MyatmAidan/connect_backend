<?php

namespace App\Http\Requests\Api\V1\Mobile\Connection;

use Illuminate\Foundation\Http\FormRequest;

class StoreConnectionRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_id' => ['required', 'ulid', 'exists:users,id'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
