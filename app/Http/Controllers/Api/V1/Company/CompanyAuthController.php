<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CompanyProfileResource;
use App\Models\Company;
use App\Services\CompanyProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CompanyAuthController extends Controller
{
    public function register(Request $request, CompanyProfileService $profiles)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:companies,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'website' => ['nullable', 'url'],
            'location' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $result = DB::transaction(function () use ($data, $profiles) {
            $company = Company::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $profiles->createForCompany($company, $data);

            return $company->load('companyProfile');
        });

        $token = $result->createToken('company')->plainTextToken;

        return ApiResponse::success([
            'company' => new CompanyProfileResource($result->companyProfile),
            'token' => $token,
        ], 'Company account created.', 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $company = Company::query()->where('email', $request->email)->first();

        if (! $company || ! Hash::check($request->password, $company->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $company->load('companyProfile');
        $token = $company->createToken('company')->plainTextToken;

        return ApiResponse::success([
            'company' => new CompanyProfileResource($company->companyProfile),
            'token' => $token,
        ], 'Logged in.');
    }

    public function me(Request $request)
    {
        /** @var Company $company */
        $company = $request->user();

        return ApiResponse::success(
            new CompanyProfileResource($company->companyProfile()->with('company')->first())
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logged out.');
    }
}
