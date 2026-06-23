<?php

namespace App\Http\Requests\Api\V1\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:100', 'unique:categories,name_en'],
            'name_my' => ['required', 'string', 'max:100', 'unique:categories,name_my'],
        ];
    }
}
