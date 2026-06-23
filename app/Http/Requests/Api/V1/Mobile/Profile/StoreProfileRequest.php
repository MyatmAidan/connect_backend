<?php

namespace App\Http\Requests\Api\V1\Mobile\Profile;

use App\Enums\ExperienceLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nullable = [
            'profile_photo',
            'headline',
            'bio',
            'experience_level',
            'location',
            'github_url',
            'linkedin_url',
            'portfolio_url',
            'phone',
            'category_id',
        ];

        $merged = [];
        foreach ($nullable as $key) {
            if ($this->has($key) && $this->input($key) === '') {
                $merged[$key] = null;
            }
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        return [
            'profile_photo' => ['nullable', 'string', 'max:500'],
            'headline' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'category_id' => ['nullable', 'ulid', 'exists:categories,id'],
            'experience_level' => ['nullable', Rule::enum(ExperienceLevel::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'github_url' => ['nullable', 'url'],
            'linkedin_url' => ['nullable', 'url'],
            'portfolio_url' => ['nullable', 'url'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_public' => ['sometimes', 'boolean'],
            'skill_ids' => ['sometimes', 'array'],
            'skill_ids.*' => ['ulid', 'exists:skills,id'],
        ];
    }
}
