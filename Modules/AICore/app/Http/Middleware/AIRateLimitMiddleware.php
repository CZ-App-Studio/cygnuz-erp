<?php

namespace Modules\AICore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Modules\AICore\Exceptions\AIRateLimitException;
use Symfony\Component\HttpFoundation\Response;

class AIRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if rate limiting is enabled
        if (! setting('aicore.rate_limit_enabled', true)) {
            return $next($request);
        }

        $user = $request->user();
        $userId = $user ? $user->id : 'guest';

        // Get rate limits from settings
        $globalLimit = setting('aicore.global_rate_limit', 60);
        $userLimit = setting('aicore.user_rate_limit', 20);

        // Global rate limiting
        $globalKey = 'ai-global-rate-limit';
        if (! $this->attemptRateLimit($globalKey, $globalLimit)) {
            throw new AIRateLimitException('Global AI rate limit exceeded. Please wait a moment and try again.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Per-user rate limiting
        if ($user) {
            $userKey = "ai-user-rate-limit:{$userId}";
            if (! $this->attemptRateLimit($userKey, $userLimit)) {
                throw new AIRateLimitException('User AI rate limit exceeded. Please wait a moment and try again.', Response::HTTP_TOO_MANY_REQUESTS);
            }
        }

        return $next($request);
    }

    /**
     * Attempt to pass rate limit check
     */
    protected function attemptRateLimit(string $key, int $maxAttempts): bool
    {
        // Use 1 minute window (60 seconds)
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        RateLimiter::hit($key, $decaySeconds);

        return true;
    }
}
