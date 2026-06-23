<?php

namespace App\Http\Requests\Api\V1\Mobile\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'type' => ['sometimes', 'string', 'in:text,image,file'],
        ];
    }
}
