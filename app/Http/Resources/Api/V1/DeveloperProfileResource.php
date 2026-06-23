<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeveloperProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'company_id' => $this->company_id,
            'employer' => $this->whenLoaded('employer', fn () => $this->employer ? [
                'id' => $this->employer->id,
                'company_name' => $this->employer->companyProfile?->company_name ?? $this->employer->name,
                'logo' => $this->employer->companyProfile?->logo,
            ] : null),
            'category' => $this->whenLoaded('category', function () use ($request) {
                $locale = Locale::resolve($request->header('Accept-Language'));

                return [
                    'id' => $this->category->id,
                    'slug' => $this->category->slug,
                    'name_en' => $this->category->name_en,
                    'name_my' => $this->category->name_my,
                    'name' => $this->category->localizedName($locale),
                ];
            }),
            'profile_photo' => $this->profile_photo,
            'headline' => $this->headline,
            'bio' => $this->bio,
            'experience_level' => $this->experience_level instanceof \BackedEnum ? $this->experience_level->value : $this->experience_level,
            'location' => $this->location,
            'github_url' => $this->github_url,
            'linkedin_url' => $this->linkedin_url,
            'portfolio_url' => $this->portfolio_url,
            'phone' => $this->phone,
            'cv_path' => $this->cv_path,
            'cv_original_name' => $this->cv_original_name,
            'is_public' => $this->is_public,
            'user' => UserResource::make($this->whenLoaded('user')),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'connection_status' => $this->when(
                $this->connection_meta !== null,
                fn () => $this->connection_meta['connection_status'] ?? 'none',
            ),
            'connection_id' => $this->when(
                $this->connection_meta !== null,
                fn () => $this->connection_meta['connection_id'] ?? null,
            ),
            'conversation_id' => $this->when(
                $this->connection_meta !== null,
                fn () => $this->connection_meta['conversation_id'] ?? null,
            ),
            'connection_request_id' => $this->when(
                $this->connection_meta !== null,
                fn () => $this->connection_meta['connection_request_id'] ?? null,
            ),
            'created_at' => $this->created_at,
        ];
    }
}
