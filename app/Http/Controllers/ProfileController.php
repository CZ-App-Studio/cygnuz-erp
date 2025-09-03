<?php

namespace App\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Services\AddonService\AddonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    protected $addonService;

    public function __construct(AddonService $addonService)
    {
        $this->middleware('auth');
        $this->addonService = $addonService;
    }

    /**
     * Display user profile page
     */
    public function index()
    {
        $user = auth()->user();

        // Load user relationships
        $user->load(['roles']);

        // Check if HRCore is enabled for employee data
        if ($this->addonService->isAddonEnabled('HRCore')) {
            $user->load(['team', 'designation.department', 'shift', 'manager']);
        }

        // Check if 2FA is enabled
        $has2FA = false;
        $twoFactorData = null;
        if ($this->addonService->isAddonEnabled('TwoFactorAuth')) {
            $has2FA = $user->twoFactorAuth && $user->twoFactorAuth->enabled;
            if ($has2FA) {
                $twoFactorData = [
                    'enabled_at' => $user->twoFactorAuth->confirmed_at ?? $user->twoFactorAuth->created_at,
                    'recovery_codes_count' => count(array_filter($user->twoFactorAuth->recovery_codes ?? [])),
                    'trusted_devices' => $user->twoFactorAuth->trusted_devices ?? [],
                ];
            }
        }

        // Get recent activity/sessions
        $sessions = collect();

        // Only get sessions if using database driver
        if (config('session.driver') === 'database') {
            $sessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->orderBy('last_activity', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'ip_address' => $session->ip_address,
                        'user_agent' => $session->user_agent,
                        'last_activity' => \Carbon\Carbon::createFromTimestamp($session->last_activity),
                        'is_current' => $session->id === session()->getId(),
                    ];
                });

            // If no sessions found but user is logged in, create current session entry
            if ($sessions->isEmpty() && auth()->check()) {
                // Try to update current session with user_id
                DB::table('sessions')
                    ->where('id', session()->getId())
                    ->update([
                        'user_id' => $user->id,
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'last_activity' => now()->timestamp,
                    ]);

                // Fetch again
                $sessions = DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->orderBy('last_activity', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($session) {
                        return [
                            'id' => $session->id,
                            'ip_address' => $session->ip_address,
                            'user_agent' => $session->user_agent,
                            'last_activity' => \Carbon\Carbon::createFromTimestamp($session->last_activity),
                            'is_current' => $session->id === session()->getId(),
                        ];
                    });
            }
        }

        // Get login history if available
        $loginHistory = [];
        if (DB::getSchemaBuilder()->hasTable('login_history')) {
            $loginHistory = DB::table('login_history')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        return view('profile.index', compact('user', 'has2FA', 'twoFactorData', 'sessions', 'loginHistory'));
    }

    /**
     * Update basic profile information
     */
    public function updateBasicInfo(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($user->id),
                ],
                'phone' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('users')->ignore($user->id),
                ],
                'date_of_birth' => 'nullable|date|before:today',
                'gender' => 'nullable|in:male,female,other',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return Error::response([
                    'message' => __('Validation failed'),
                    'errors' => $validator->errors(),
                ]);
            }

            $user->update($request->only([
                'name', 'email', 'phone', 'date_of_birth',
                'gender', 'address', 'city', 'state',
                'country', 'postal_code',
            ]));

            return Success::response([
                'message' => __('Profile updated successfully'),
                'data' => $user,
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to update profile'));
        }
    }

    /**
     * Update profile picture
     */
    public function updateProfilePicture(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return Error::response([
                    'message' => __('Validation failed'),
                    'errors' => $validator->errors(),
                ]);
            }

            $user = auth()->user();

            // Delete old profile picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Store new profile picture
            $path = $request->file('profile_picture')->store('profile-pictures', 'public');

            $user->update(['profile_picture' => $path]);

            return Success::response([
                'message' => __('Profile picture updated successfully'),
                'data' => [
                    'profile_picture_url' => Storage::url($path),
                ],
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to update profile picture'));
        }
    }

    /**
     * Remove profile picture
     */
    public function removeProfilePicture()
    {
        try {
            $user = auth()->user();

            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
                $user->update(['profile_picture' => null]);
            }

            return Success::response([
                'message' => __('Profile picture removed successfully'),
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to remove profile picture'));
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => [
                    'required',
                    'confirmed',
                    Password::min(8)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised(),
                ],
            ]);

            if ($validator->fails()) {
                return Error::response([
                    'message' => __('Validation failed'),
                    'errors' => $validator->errors(),
                ]);
            }

            $user = auth()->user();

            // Check if current password is correct
            if (! Hash::check($request->current_password, $user->password)) {
                return Error::response(__('Current password is incorrect'));
            }

            // Check if new password is same as current
            if (Hash::check($request->new_password, $user->password)) {
                return Error::response(__('New password cannot be the same as current password'));
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            // Log password change activity
            if (DB::getSchemaBuilder()->hasTable('user_activities')) {
                DB::table('user_activities')->insert([
                    'user_id' => $user->id,
                    'activity' => 'password_changed',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return Success::response([
                'message' => __('Password changed successfully'),
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to change password'));
        }
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email_notifications' => 'boolean',
                'push_notifications' => 'boolean',
                'sms_notifications' => 'boolean',
                'newsletter' => 'boolean',
                'marketing_emails' => 'boolean',
            ]);

            if ($validator->fails()) {
                return Error::response([
                    'message' => __('Validation failed'),
                    'errors' => $validator->errors(),
                ]);
            }

            $user = auth()->user();

            // Store preferences in user_preferences table or user meta
            $preferences = $request->only([
                'email_notifications',
                'push_notifications',
                'sms_notifications',
                'newsletter',
                'marketing_emails',
            ]);

            // If user_preferences table exists
            if (DB::getSchemaBuilder()->hasTable('user_preferences')) {
                DB::table('user_preferences')->updateOrInsert(
                    ['user_id' => $user->id],
                    array_merge($preferences, [
                        'updated_at' => now(),
                    ])
                );
            }

            return Success::response([
                'message' => __('Notification preferences updated successfully'),
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to update notification preferences'));
        }
    }

    /**
     * Get active sessions
     */
    public function getSessions()
    {
        try {
            $user = auth()->user();

            $sessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->orderBy('last_activity', 'desc')
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'ip_address' => $session->ip_address,
                        'user_agent' => $this->parseUserAgent($session->user_agent),
                        'last_activity' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                        'is_current' => $session->id === session()->getId(),
                    ];
                });

            return Success::response([
                'data' => $sessions,
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to fetch sessions'));
        }
    }

    /**
     * Terminate a session
     */
    public function terminateSession(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return Error::response([
                    'message' => __('Validation failed'),
                    'errors' => $validator->errors(),
                ]);
            }

            // Don't allow terminating current session
            if ($request->session_id === session()->getId()) {
                return Error::response(__('Cannot terminate current session'));
            }

            DB::table('sessions')
                ->where('id', $request->session_id)
                ->where('user_id', auth()->id())
                ->delete();

            return Success::response([
                'message' => __('Session terminated successfully'),
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to terminate session'));
        }
    }

    /**
     * Terminate all other sessions
     */
    public function terminateAllSessions(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return Error::response([
                    'message' => __('Validation failed'),
                    'errors' => $validator->errors(),
                ]);
            }

            $user = auth()->user();

            // Verify password
            if (! Hash::check($request->password, $user->password)) {
                return Error::response(__('Password is incorrect'));
            }

            // Delete all sessions except current
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', session()->getId())
                ->delete();

            return Success::response([
                'message' => __('All other sessions terminated successfully'),
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to terminate sessions'));
        }
    }

    /**
     * Parse user agent string
     */
    private function parseUserAgent($userAgent)
    {
        $browser = 'Unknown Browser';
        $platform = 'Unknown OS';

        // Detect browser
        if (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
        }

        // Detect platform
        if (preg_match('/Windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/Mac/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iPhone|iPad/i', $userAgent)) {
            $platform = 'iOS';
        }

        return "$browser on $platform";
    }
}
