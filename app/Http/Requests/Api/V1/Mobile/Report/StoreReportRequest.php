<?php

namespace App\Http\Requests\Api\V1\Mobile\Report;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reported_user_id' => ['required', 'ulid', 'exists:users,id', 'different:reporter'],
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reporter' => $this->user()?->id,
        ]);
    }
}
