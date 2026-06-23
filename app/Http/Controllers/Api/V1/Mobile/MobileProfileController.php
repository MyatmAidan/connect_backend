<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Mobile\Profile\StoreProfileRequest;
use App\Http\Requests\Api\V1\Mobile\Profile\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Mobile\Profile\UploadProfilePhotoRequest;
use App\Http\Resources\Api\V1\DeveloperProfileResource;
use App\Services\DeveloperProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MobileProfileController extends Controller
{
    public function __construct(private readonly DeveloperProfileService $profiles)
    {
    }

    public function showMe(Request $request)
    {
        $profile = $this->profiles->showForUser($request->user());

        return ApiResponse::success(
            $profile ? new DeveloperProfileResource($profile) : null
        );
    }

    public function store(StoreProfileRequest $request)
    {
        $user = $request->user()->loadMissing('developerProfile');
        $hadProfile = $user->developerProfile !== null;
        $profile = $this->profiles->store($user, $request->validated());

        if ($hadProfile) {
            return ApiResponse::success(new DeveloperProfileResource($profile), 'Profile updated.');
        }

        return ApiResponse::success(new DeveloperProfileResource($profile), 'Profile created.', 201);
    }

    public function updateMe(UpdateProfileRequest $request)
    {
        $profile = $request->user()->developerProfile;

        if (! $profile) {
            return ApiResponse::error('Developer profile not found.', 404);
        }

        $updated = $this->profiles->update($profile, $request->validated());

        return ApiResponse::success(new DeveloperProfileResource($updated), 'Profile updated.');
    }

    public function uploadPhoto(UploadProfilePhotoRequest $request)
    {
        $url = $this->profiles->uploadPhoto($request->user(), $request->file('photo'));

        return ApiResponse::success([
            'profile_photo' => $url,
        ], 'Profile photo uploaded.');
    }

    public function uploadCv(Request $request)
    {
        $request->validate([
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $profile = $request->user()->developerProfile;

        if (! $profile) {
            return ApiResponse::error('Developer profile not found.', 404);
        }

        $result = $this->profiles->uploadCv($request->user(), $request->file('cv'));

        return ApiResponse::success($result, 'CV uploaded.');
    }

    public function destroyMe(Request $request)
    {
        $profile = $request->user()->developerProfile;

        if (! $profile) {
            return ApiResponse::error('Developer profile not found.', 404);
        }

        $this->profiles->delete($profile);

        return ApiResponse::success(null, 'Profile deleted.');
    }
}
