<?php

namespace App\Http\Requests\Api\V1\Mobile\Conversation;

use Illuminate\Foundation\Http\FormRequest;

class ReorderPinnedConversationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'ulid', 'exists:conversations,id'],
        ];
    }
}
