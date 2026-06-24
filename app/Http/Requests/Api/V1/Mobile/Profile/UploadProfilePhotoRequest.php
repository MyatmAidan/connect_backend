<?php

namespace App\Http\Requests\Api\V1\Mobile\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UploadProfilePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:10240'],
        ];
    }
}
