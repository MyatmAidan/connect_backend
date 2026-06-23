<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_id' => $this->job_posting_id,
            'applicant_id' => $this->applicant_id,
            'cover_letter' => $this->cover_letter,
            'status' => $this->status,
            'company_notes' => $this->when(
                $request->user() instanceof Company || ($request->user() instanceof User && $request->user()->isAdmin()),
                $this->company_notes,
            ),
            'reviewed_at' => $this->reviewed_at,
            'interview_ack_sent_at' => $this->when(
                $request->user() instanceof Company || ($request->user() instanceof User && $request->user()->isAdmin()),
                $this->interview_ack_sent_at,
            ),
            'job' => JobResource::make($this->whenLoaded('job')),
            'applicant' => UserResource::make($this->whenLoaded('applicant')),
            'created_at' => $this->created_at,
        ];
    }
}
