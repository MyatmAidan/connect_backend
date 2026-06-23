<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            // Throttle DB writes — only update if last stamp is older than 60 seconds
            $lastActive = $user->last_active_at;
            if (! $lastActive || $lastActive->diffInSeconds(now()) > 60) {
                $user->timestamps = false;
                $user->updateQuietly(['last_active_at' => now()]);
                $user->timestamps = true;
            }
        }

        return $next($request);
    }
}
