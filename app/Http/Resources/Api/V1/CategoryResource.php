<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = Locale::resolve($request->header('Accept-Language'));

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name_en' => $this->name_en,
            'name_my' => $this->name_my,
            'name' => $this->localizedName($locale),
        ];
    }
}
