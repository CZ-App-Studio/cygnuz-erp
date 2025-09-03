<?php

namespace App\Http\Middleware;

use App\Services\Settings\ModuleSettingsService;
use App\Services\Settings\SettingsCacheManager;
use App\Services\Settings\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class LoadSettings
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cache = app(SettingsCacheManager::class);

        $settings = $cache->remember('global_settings', function () {
            return [
                'system' => app(SettingsService::class)->all(),
                'modules' => app(ModuleSettingsService::class)->getAllGrouped(),
            ];
        });

        // Create a settings object with the old structure for backward compatibility
        $settingsObject = (object) $settings['system']->toArray();

        // Share settings with all views
        View::share('settings', $settingsObject);
        View::share('systemSettings', $settings['system']);
        View::share('moduleSettings', $settings['modules']);

        return $next($request);
    }
}
