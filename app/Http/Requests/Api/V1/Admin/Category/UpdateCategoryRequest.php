<?php

namespace App\Http\Requests\Api\V1\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name_en' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name_en')->ignore($category?->id),
            ],
            'name_my' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name_my')->ignore($category?->id),
            ],
        ];
    }
}
