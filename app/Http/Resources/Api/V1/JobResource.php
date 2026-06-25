<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $myApplication = ($user instanceof User)
            ? $this->applications->first()
            : null;

        return [
            'id' => $this->id,
            'company_profile_id' => $this->company_profile_id,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'slug' => $this->category->slug,
                'name_en' => $this->category->name_en,
                'name_my' => $this->category->name_my,
            ]),
            'title' => $this->title,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'employment_type' => $this->employment_type,
            'experience_level' => $this->experience_level instanceof \BackedEnum ? $this->experience_level->value : $this->experience_level,
            'location' => $this->location,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'salary_currency' => $this->salary_currency,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'closes_at' => $this->closes_at,
            'can_apply' => $this->isAcceptingApplications(),
            'company_profile' => CompanyProfileResource::make($this->whenLoaded('companyProfile')),
            'applications_count' => $this->when(isset($this->applications_count), $this->applications_count),
            'has_applied' => $this->when($user instanceof User, (bool) ($this->has_applied ?? $myApplication)),
            'my_application_status' => $this->when(
                $user instanceof User && ($myApplication || $this->has_applied),
                fn() => $myApplication?->status instanceof \BackedEnum
                    ? $myApplication->status->value
                    : $myApplication?->status,
            ),
            'created_at' => $this->created_at,
        ];
    }
}
