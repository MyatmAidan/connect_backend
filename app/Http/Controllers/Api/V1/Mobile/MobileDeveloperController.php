<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DeveloperProfileResource;
use App\Models\DeveloperProfile;
use App\Services\ConnectionStatusService;
use App\Services\DeveloperProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MobileDeveloperController extends Controller
{
    public function __construct(
        private readonly DeveloperProfileService $profiles,
        private readonly ConnectionStatusService $connectionStatus,
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->profiles->list($request->only([
            'role', 'skill', 'experience_level',
        ]), (int) $request->get('per_page', 15));

        $profiles = $this->connectionStatus->enrichProfiles(
            $request->user(),
            $paginator->getCollection(),
        );
        $paginator->setCollection($profiles);

        return ApiResponse::paginated(
            $paginator,
            DeveloperProfileResource::collection($paginator)->resolve()
        );
    }

    public function show(DeveloperProfile $developerProfile, Request $request)
    {
        $profile = $this->profiles->show($developerProfile->id);

        if (! $profile?->is_public) {
            return ApiResponse::error('Developer profile not found.', 404);
        }

        $profile->setAttribute(
            'connection_meta',
            $this->connectionStatus->between($request->user(), $profile->user_id),
        );

        return ApiResponse::success(new DeveloperProfileResource($profile));
    }
}
