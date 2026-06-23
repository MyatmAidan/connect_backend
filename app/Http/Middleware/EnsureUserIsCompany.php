<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Company) {
            return ApiResponse::error('Company account required.', 403);
        }

        return $next($request);
    }
}
