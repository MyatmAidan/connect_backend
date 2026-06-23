<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\DeveloperProfile\UpdateDeveloperProfileRequest;
use App\Http\Resources\Api\V1\DeveloperProfileResource;
use App\Models\DeveloperProfile;
use App\Services\AdminLogService;
use App\Services\DeveloperProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminDeveloperProfileController extends Controller
{
    public function __construct(
        private readonly DeveloperProfileService $profiles,
        private readonly AdminLogService $adminLogs,
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->profiles->list(
            array_merge($request->only(['role', 'skill', 'experience_level']), ['is_public' => null]),
            (int) $request->get('per_page', 15)
        );

        return ApiResponse::paginated($paginator, DeveloperProfileResource::collection($paginator)->resolve());
    }

    public function show(DeveloperProfile $developerProfile)
    {
        return ApiResponse::success(new DeveloperProfileResource($this->profiles->show($developerProfile->id)));
    }

    public function update(UpdateDeveloperProfileRequest $request, DeveloperProfile $developerProfile)
    {
        $updated = $this->profiles->update($developerProfile, $request->validated());

        $this->adminLogs->log(
            $request->user(),
            'update_developer_profile',
            DeveloperProfile::class,
            $developerProfile->id,
            'Updated developer profile '.$developerProfile->id,
        );

        return ApiResponse::success(new DeveloperProfileResource($updated), 'Profile updated.');
    }

    public function destroy(DeveloperProfile $developerProfile, Request $request)
    {
        $profileId = $developerProfile->id;
        $this->profiles->delete($developerProfile);

        $this->adminLogs->log(
            $request->user(),
            'delete_developer_profile',
            DeveloperProfile::class,
            $profileId,
            'Deleted developer profile '.$profileId,
        );

        return ApiResponse::success(null, 'Profile deleted.');
    }
}
