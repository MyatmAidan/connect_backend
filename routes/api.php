<?php

use App\Http\Controllers\Api\V1\Admin\AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\AdminCompanyController;
use App\Http\Controllers\Api\V1\Admin\AdminJobApplicationController;
use App\Http\Controllers\Api\V1\Admin\AdminJobController;
use App\Http\Controllers\Api\V1\Admin\AdminConnectionController;
use App\Http\Controllers\Api\V1\Admin\AdminConnectionRequestController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminDeveloperProfileController;
use App\Http\Controllers\Api\V1\Admin\AdminLogController;
use App\Http\Controllers\Api\V1\Admin\AdminEventRegistrationController;
use App\Http\Controllers\Api\V1\Admin\AdminEventController;
use App\Http\Controllers\Api\V1\Admin\AdminEventRequestController;
use App\Http\Controllers\Api\V1\Admin\AdminNotificationController;
use App\Http\Controllers\Api\V1\Admin\AdminReportController;
use App\Http\Controllers\Api\V1\Admin\AdminSkillController;
use App\Http\Controllers\Api\V1\Admin\AdminTelegramController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Controllers\Api\V1\Company\CompanyAuthController;
use App\Http\Controllers\Api\V1\Company\CompanyDashboardController;
use App\Http\Controllers\Api\V1\Company\CompanyCategoryController;
use App\Http\Controllers\Api\V1\Company\CompanyJobApplicationController;
use App\Http\Controllers\Api\V1\Company\CompanyJobController;
use App\Http\Controllers\Api\V1\Company\CompanyProfileController;
use App\Http\Controllers\Api\V1\Mobile\MobileCategoryController;
use App\Http\Controllers\Api\V1\Mobile\MobileJobApplicationController;
use App\Http\Controllers\Api\V1\Mobile\MobileJobController;
use App\Http\Controllers\Api\V1\Mobile\MobileAuthController;
use App\Http\Controllers\Api\V1\Mobile\MobileBlockController;
use App\Http\Controllers\Api\V1\Mobile\MobileConnectionController;
use App\Http\Controllers\Api\V1\Mobile\MobileConnectionRequestController;
use App\Http\Controllers\Api\V1\Mobile\MobileConversationController;
use App\Http\Controllers\Api\V1\Mobile\MobileDeveloperController;
use App\Http\Controllers\Api\V1\Mobile\MobileEventRegistrationController;
use App\Http\Controllers\Api\V1\Mobile\MobileEventController;
use App\Http\Controllers\Api\V1\Mobile\MobileEventRequestController;
use App\Http\Controllers\Api\V1\Mobile\MobileMessageController;
use App\Http\Controllers\Api\V1\Mobile\MobileNotificationController;
use App\Http\Controllers\Api\V1\Mobile\MobileProfileController;
use App\Http\Controllers\Api\V1\Mobile\MobileReportController;
use App\Http\Controllers\Api\V1\Mobile\MobileSkillController;
use App\Http\Controllers\Api\V1\Mobile\MobileTelegramController;
use App\Http\Controllers\Api\V1\Telegram\TelegramWebhookController;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);

    Route::prefix('mobile')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/register', [MobileAuthController::class, 'register']);
            Route::post('/login', [MobileAuthController::class, 'login']);
        });

        Route::middleware(['auth:sanctum', 'active'])->group(function () {
            Route::get('/auth/me', [MobileAuthController::class, 'me']);
            Route::post('/auth/logout', [MobileAuthController::class, 'logout']);

            Route::get('/profile/me', [MobileProfileController::class, 'showMe']);
            Route::post('/profile', [MobileProfileController::class, 'store']);
            Route::put('/profile/me', [MobileProfileController::class, 'updateMe']);
            Route::post('/profile/me/photo', [MobileProfileController::class, 'uploadPhoto']);
            Route::post('/profile/me/cv', [MobileProfileController::class, 'uploadCv']);
            Route::delete('/profile/me', [MobileProfileController::class, 'destroyMe']);

            Route::get('/jobs', [MobileJobController::class, 'index']);
            Route::get('/jobs/{job}', [MobileJobController::class, 'show']);
            Route::post('/jobs/{job}/apply', [MobileJobApplicationController::class, 'apply']);
            Route::get('/job-applications/me', [MobileJobApplicationController::class, 'myApplications']);
            Route::post('/job-applications/{jobApplication}/withdraw', [MobileJobApplicationController::class, 'withdraw']);

            Route::get('/developers', [MobileDeveloperController::class, 'index']);
            Route::get('/developers/{developerProfile}', [MobileDeveloperController::class, 'show']);

            Route::get('/categories', [MobileCategoryController::class, 'index']);
            Route::get('/skills', [MobileSkillController::class, 'index']);

            Route::post('/connection-requests', [MobileConnectionRequestController::class, 'store']);
            Route::get('/connection-requests/received', [MobileConnectionRequestController::class, 'received']);
            Route::get('/connection-requests/sent', [MobileConnectionRequestController::class, 'sent']);
            Route::post('/connection-requests/{connectionRequest}/accept', [MobileConnectionRequestController::class, 'accept']);
            Route::post('/connection-requests/{connectionRequest}/reject', [MobileConnectionRequestController::class, 'reject']);
            Route::post('/connection-requests/{connectionRequest}/cancel', [MobileConnectionRequestController::class, 'cancel']);

            Route::get('/connections', [MobileConnectionController::class, 'index']);
            Route::get('/connections/{connection}', [MobileConnectionController::class, 'show']);
            Route::delete('/connections/{connection}', [MobileConnectionController::class, 'destroy']);

            Route::get('/conversations', [MobileConversationController::class, 'index']);
            Route::post('/conversations/pinned/reorder', [MobileConversationController::class, 'reorderPinned']);
            Route::get('/conversations/{conversation}', [MobileConversationController::class, 'show']);
            Route::post('/conversations/{conversation}/pin', [MobileConversationController::class, 'pin']);
            Route::delete('/conversations/{conversation}/pin', [MobileConversationController::class, 'unpin']);
            Route::post('/conversations/{conversation}/mute', [MobileConversationController::class, 'mute']);
            Route::delete('/conversations/{conversation}/mute', [MobileConversationController::class, 'unmute']);
            Route::delete('/conversations/{conversation}', [MobileConversationController::class, 'destroy']);
            Route::get('/conversations/{conversation}/messages', [MobileMessageController::class, 'index']);
            Route::post('/conversations/{conversation}/messages', [MobileMessageController::class, 'store']);
            Route::put('/conversations/{conversation}/messages/{message}', [MobileMessageController::class, 'update']);
            Route::delete('/conversations/{conversation}/messages/{message}', [MobileMessageController::class, 'destroy']);
            Route::post('/conversations/{conversation}/messages/{message}/pin', [MobileMessageController::class, 'pin']);
            Route::delete('/conversations/{conversation}/messages/{message}/pin', [MobileMessageController::class, 'unpin']);
            Route::post('/conversations/{conversation}/read', [MobileMessageController::class, 'markAsRead']);

            Route::get('/events', [MobileEventController::class, 'index']);
            Route::get('/events/{event}', [MobileEventController::class, 'show']);
            Route::get('/events/{event}/registrations', [MobileEventRegistrationController::class, 'index']);
            Route::post('/events/{event}/registrations', [MobileEventRegistrationController::class, 'store']);
            Route::post('/events/{event}/registrations/{eventRegistration}/accept', [MobileEventRegistrationController::class, 'accept']);
            Route::post('/events/{event}/registrations/{eventRegistration}/reject', [MobileEventRegistrationController::class, 'reject']);
            Route::get('/event-requests', [MobileEventRequestController::class, 'index']);
            Route::post('/event-requests', [MobileEventRequestController::class, 'store']);

            Route::post('/telegram/link-token', [MobileTelegramController::class, 'createLinkToken']);
            Route::post('/telegram/test', [MobileTelegramController::class, 'sendTest']);
            Route::put('/telegram/settings', [MobileTelegramController::class, 'updateSettings']);
            Route::delete('/telegram/disconnect', [MobileTelegramController::class, 'disconnect']);

            Route::get('/notifications', [MobileNotificationController::class, 'index']);
            Route::get('/notifications/unread-count', [MobileNotificationController::class, 'unreadCount']);
            Route::get('/notifications/{notification}', [MobileNotificationController::class, 'show']);
            Route::post('/notifications/{notification}/read', [MobileNotificationController::class, 'markAsRead']);
            Route::post('/notifications/read-all', [MobileNotificationController::class, 'markAllAsRead']);

            Route::post('/reports', [MobileReportController::class, 'store']);

            Route::post('/users/{user}/block', [MobileBlockController::class, 'block']);
            Route::delete('/users/{user}/block', [MobileBlockController::class, 'unblock']);
            Route::get('/blocked-users', [MobileBlockController::class, 'index']);
        });
    });

    Route::prefix('admin')->group(function () {
        Route::post('/auth/login', function (Request $request, AuthService $auth) {
            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            $result = $auth->login($request->email, $request->password);

            if (! $result['user']->isAdmin()) {
                $result['user']->currentAccessToken()?->delete();

                return ApiResponse::error('Admin access required.', 403);
            }

            return ApiResponse::success([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Admin logged in.');
        });

        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::get('/auth/me', fn (Request $request) => ApiResponse::success(
                new UserResource($request->user())
            ));
            Route::post('/auth/logout', function (Request $request) {
                $request->user()->currentAccessToken()?->delete();

                return ApiResponse::success(null, 'Logged out.');
            });

            Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);
            Route::get('/dashboard/user-growth', [AdminDashboardController::class, 'userGrowth']);
            Route::get('/dashboard/activity', [AdminDashboardController::class, 'activity']);
            Route::get('/dashboard/charts', [AdminDashboardController::class, 'charts']);

            Route::get('/users', [AdminUserController::class, 'index']);
            Route::get('/users/{user}', [AdminUserController::class, 'show']);
            Route::put('/users/{user}', [AdminUserController::class, 'update']);
            Route::post('/users/{user}/ban', [AdminUserController::class, 'ban']);
            Route::post('/users/{user}/unban', [AdminUserController::class, 'unban']);
            Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

            Route::get('/developer-profiles', [AdminDeveloperProfileController::class, 'index']);
            Route::get('/developer-profiles/{developerProfile}', [AdminDeveloperProfileController::class, 'show']);
            Route::put('/developer-profiles/{developerProfile}', [AdminDeveloperProfileController::class, 'update']);
            Route::delete('/developer-profiles/{developerProfile}', [AdminDeveloperProfileController::class, 'destroy']);

            Route::get('/categories', [AdminCategoryController::class, 'index']);
            Route::post('/categories', [AdminCategoryController::class, 'store']);
            Route::put('/categories/{category}', [AdminCategoryController::class, 'update']);
            Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);

            Route::get('/skills', [AdminSkillController::class, 'index']);
            Route::post('/skills', [AdminSkillController::class, 'store']);
            Route::put('/skills/{skill}', [AdminSkillController::class, 'update']);
            Route::delete('/skills/{skill}', [AdminSkillController::class, 'destroy']);

            Route::get('/connection-requests', [AdminConnectionRequestController::class, 'index']);
            Route::get('/connections', [AdminConnectionController::class, 'index']);
            Route::get('/connections/{connection}', [AdminConnectionController::class, 'show']);
            Route::delete('/connections/{connection}', [AdminConnectionController::class, 'destroy']);

            Route::get('/events', [AdminEventController::class, 'index']);
            Route::post('/events/reorder', [AdminEventController::class, 'reorder']);
            Route::post('/events', [AdminEventController::class, 'store']);
            Route::get('/events/{event}', [AdminEventController::class, 'show']);
            Route::put('/events/{event}', [AdminEventController::class, 'update']);
            Route::delete('/events/{event}', [AdminEventController::class, 'destroy']);
            Route::get('/events/{event}/registrations', [AdminEventRegistrationController::class, 'index']);
            Route::post('/events/{event}/registrations/{eventRegistration}/accept', [AdminEventRegistrationController::class, 'accept']);
            Route::post('/events/{event}/registrations/{eventRegistration}/reject', [AdminEventRegistrationController::class, 'reject']);

            Route::get('/event-requests', [AdminEventRequestController::class, 'index']);
            Route::get('/event-requests/{eventRequest}', [AdminEventRequestController::class, 'show']);
            Route::post('/event-requests/{eventRequest}/approve', [AdminEventRequestController::class, 'approve']);
            Route::post('/event-requests/{eventRequest}/reject', [AdminEventRequestController::class, 'reject']);

            Route::get('/reports', [AdminReportController::class, 'index']);
            Route::get('/reports/{report}', [AdminReportController::class, 'show']);
            Route::post('/reports/{report}/review', [AdminReportController::class, 'review']);
            Route::post('/reports/{report}/resolve', [AdminReportController::class, 'resolve']);
            Route::post('/reports/{report}/reject', [AdminReportController::class, 'reject']);

            Route::get('/notifications', [AdminNotificationController::class, 'index']);
            Route::post('/notifications/broadcast', [AdminNotificationController::class, 'broadcast']);

            Route::get('/telegram/stats', [AdminTelegramController::class, 'stats']);
            Route::get('/telegram/logs', [AdminTelegramController::class, 'logs']);

            Route::get('/logs', [AdminLogController::class, 'index']);
            Route::get('/logs/{adminLog}', [AdminLogController::class, 'show']);

            Route::get('/companies', [AdminCompanyController::class, 'index']);
            Route::get('/companies/{companyProfile}', [AdminCompanyController::class, 'show']);
            Route::put('/companies/{companyProfile}', [AdminCompanyController::class, 'update']);

            Route::get('/jobs', [AdminJobController::class, 'index']);
            Route::get('/jobs/{job}', [AdminJobController::class, 'show']);
            Route::put('/jobs/{job}', [AdminJobController::class, 'update']);
            Route::delete('/jobs/{job}', [AdminJobController::class, 'destroy']);

            Route::get('/job-applications', [AdminJobApplicationController::class, 'index']);
            Route::get('/job-applications/{jobApplication}', [AdminJobApplicationController::class, 'show']);
        });
    });

    Route::prefix('company')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/register', [CompanyAuthController::class, 'register']);
            Route::post('/login', [CompanyAuthController::class, 'login']);
        });

        Route::get('/categories', [CompanyCategoryController::class, 'index']);

        Route::middleware(['auth:sanctum', 'company'])->group(function () {
            Route::get('/auth/me', [CompanyAuthController::class, 'me']);
            Route::post('/auth/logout', [CompanyAuthController::class, 'logout']);

            Route::get('/dashboard/stats', [CompanyDashboardController::class, 'stats']);

            Route::get('/profile', [CompanyProfileController::class, 'show']);
            Route::put('/profile', [CompanyProfileController::class, 'update']);
            Route::post('/profile/logo', [CompanyProfileController::class, 'uploadLogo']);

            Route::get('/jobs', [CompanyJobController::class, 'index']);
            Route::post('/jobs', [CompanyJobController::class, 'store']);
            Route::get('/jobs/{job}', [CompanyJobController::class, 'show']);
            Route::put('/jobs/{job}', [CompanyJobController::class, 'update']);
            Route::post('/jobs/{job}/publish', [CompanyJobController::class, 'publish']);
            Route::post('/jobs/{job}/close', [CompanyJobController::class, 'close']);
            Route::delete('/jobs/{job}', [CompanyJobController::class, 'destroy']);

            Route::get('/job-applications', [CompanyJobApplicationController::class, 'index']);
            Route::get('/job-applications/{jobApplication}', [CompanyJobApplicationController::class, 'show']);
            Route::put('/job-applications/{jobApplication}/status', [CompanyJobApplicationController::class, 'updateStatus']);
            Route::post('/job-applications/{jobApplication}/interview-ack', [CompanyJobApplicationController::class, 'sendInterviewAcknowledgment']);
        });
    });
});
