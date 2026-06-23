<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company_name' => $this->company_name,
            'description' => $this->description,
            'logo' => $this->logo,
            'website' => $this->website,
            'location' => $this->location,
            'industry' => $this->industry,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'is_verified' => $this->is_verified,
            'is_active' => $this->is_active,
            'company' => $this->whenLoaded('company', fn() => [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'email' => $this->company->email,
            ]),
            'jobs_count' => $this->when(isset($this->jobs_count), $this->jobs_count),
            'created_at' => $this->created_at,
        ];
    }
}
