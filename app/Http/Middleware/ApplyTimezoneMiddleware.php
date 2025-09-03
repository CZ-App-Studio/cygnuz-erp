<?php

namespace App\Http\Middleware;

use App\Helpers\TimezoneHelper;
use App\Services\Settings\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyTimezoneMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get timezone from settings
        $settingsService = app(SettingsService::class);
        $timezone = $settingsService->get('default_timezone', config('app.timezone', 'UTC'));

        // Apply timezone to the application
        if ($timezone) {
            try {
                TimezoneHelper::setApplicationTimezone($timezone);
            } catch (\InvalidArgumentException $e) {
                // Fall back to default if invalid timezone
                TimezoneHelper::setApplicationTimezone(config('app.timezone', 'UTC'));
            }
        }

        return $next($request);
    }
}
