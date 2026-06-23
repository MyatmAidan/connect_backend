<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Mobile\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Mobile\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MobileAuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function register(RegisterRequest $request)
    {
        $result = $this->auth->register($request->validated());
        $result['user']->load('developerProfile.skills');

        return ApiResponse::success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Registered successfully.', 201);
    }

    public function login(LoginRequest $request)
    {
        $result = $this->auth->login(
            $request->validated('email'),
            $request->validated('password'),
        );
        $result['user']->load('developerProfile.skills');

        return ApiResponse::success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Logged in successfully.');
    }

    public function me(Request $request)
    {
        return ApiResponse::success(new UserResource($request->user()->load('developerProfile.skills')));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logged out successfully.');
    }
}
