<?php

namespace App\Http\Controllers;

use App\Enums\UserAccountStatus;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\AddonService\AddonService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

// Optional module imports - checked before use
// use Modules\GoogleReCAPTCHA\app\Rules\ReCaptchaRule;
// use Modules\GoogleReCAPTCHA\app\Services\ReCaptchaService;
// use Modules\TwoFactorAuth\app\Services\TwoFactorAuthService;

class AuthController extends Controller
{
    protected $redirectTo = RouteServiceProvider::HOME;

    public function loginPost(Request $request)
    {
        try {

            $rules = [
                'email' => 'required|email',
                'password' => 'required|min:6',
                'rememberMe' => 'boolean',
            ];

            // Add reCAPTCHA validation if module is enabled
            $addonService = app(AddonService::class);
            if ($addonService->isAddonEnabled('GoogleReCAPTCHA')) {
                if (class_exists('\Modules\GoogleReCAPTCHA\app\Services\ReCaptchaService') &&
                    class_exists('\Modules\GoogleReCAPTCHA\app\Rules\ReCaptchaRule')) {
                    $recaptchaService = app('\Modules\GoogleReCAPTCHA\app\Services\ReCaptchaService');
                    if ($recaptchaService->isEnabled()) {
                        $rules['g-recaptcha-response'] = ['required', new \Modules\GoogleReCAPTCHA\app\Rules\ReCaptchaRule];
                    }
                }
            }

            $request->validate($rules);

            $user = User::where('email', $request->email)->first();

            if (! empty($user)) {

                if ($user->status != UserAccountStatus::ACTIVE) {
                    return redirect()->back()->with('error', 'Your account is not active. Please contact the administrator.');
                }

                $credentials = $request->only('email', 'password');

                $role = $user->roles->first();

                if (Auth::attempt($credentials, $request->rememberMe ?? false)) {
                    // Check if 2FA is required
                    Log::info('Login successful for user: '.$user->email);

                    if (class_exists(\Modules\TwoFactorAuth\app\Services\TwoFactorAuthService::class)) {
                        Log::info('TwoFactorAuthService class exists');
                        $twoFactorService = app(\Modules\TwoFactorAuth\app\Services\TwoFactorAuthService::class);
                        $isRequired = $twoFactorService->isRequired($user);
                        Log::info('2FA required check: '.($isRequired ? 'true' : 'false'));

                        if ($isRequired) {
                            // Store user ID and remember me status in session
                            session(['2fa_user_id' => $user->id]);
                            session(['2fa_remember' => $request->rememberMe ?? false]);
                            session(['2fa_pending' => true]);
                            Log::info('Redirecting to 2FA verification');

                            // Don't logout - keep user logged in but mark as pending 2FA
                            // Auth::logout();

                            // Save session before redirect
                            session()->save();

                            // Redirect to 2FA verification
                            Log::info('About to redirect to: '.route('twofactorauth.verify'));

                            return redirect('/2fa/verify');
                        }
                    } else {
                        Log::info('TwoFactorAuthService class does not exist');
                    }

                    // User is already logged in from Auth::attempt, proceed with redirect
                    if ($role->name == 'tenant') {
                        return redirect()->route('multitenancycore.tenant.dashboard')->with('success', 'Welcome back!');
                    } elseif ($role->name == 'admin' || $role->name == 'super_admin') {
                        return redirect()->route('dashboard')->with('success', 'Welcome back!');
                    } else {
                        return redirect()->route('dashboard')->with('success', 'Welcome back!');
                    }

                } else {
                    return redirect()->back()->with('error', __('Invalid username or password.'));
                }
            } else {
                return redirect()->back()->with('error', __('User not found.'));
            }
        } catch (Exception $e) {
            Log::info($e->getMessage());

            return redirect()->back()->with('error', 'Oops! You have entered invalid credentials');
        }
    }

    public function register()
    {
        // Check if MultiTenancyCore module is enabled
        $addonService = app(\App\Services\AddonService\IAddonService::class);
        if ($addonService->isAddonEnabled('MultiTenancyCore')) {
            // Redirect to tenant registration
            return redirect()->route('multitenancycore.register');
        }

        $pageConfigs = ['myLayout' => 'blank'];

        return view('auth.register', ['pageConfigs' => $pageConfigs]);
    }

    public function registerPost(Request $request)
    {
        $rules = [
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'gender' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ];

        // Add reCAPTCHA validation if enabled
        $recaptchaService = app(ReCaptchaService::class);
        if ($recaptchaService->isEnabled()) {
            $rules['g-recaptcha-response'] = ['required', new ReCaptchaRule];
        }

        $request->validate($rules);

        $user = new User;
        $user->first_name = $request->firstName;
        $user->last_name = $request->lastName;
        $user->gender = $request->gender;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->email_verified_at = env('APP_DEMO') ? now() : null;
        $user->save();

        $user->assignRole('customer');

        auth()->login($user);

        if (! env('APP_DEMO')) {
            $user->sendEmailVerificationNotification();
        }

        return redirect()->route('verification.notice')->with('success', 'Account created successfully, please verify your email address.');
    }

    public function login()
    {

        if (auth()->user()) {
            return redirect('/');
        }

        /*   if (auth()->user()) {

             if (auth()->user()->hasRole('super_admin')) {
               return redirect()->route('superAdmin.dashboard')->with('success', 'Welcome back!');
             } else {

               if(tenancy()->initialized)
               {
                 return redirect()->route('customer.dashboard')->with('success', 'Welcome back!');
               }

               if (auth()->user()->email_verified_at == null) {
                 return redirect()->route('verification.notice')->with('error', 'Please verify your email address');
               }
               if(auth()->user()->hasRole('user')) {
                 return redirect()->route('customer.dashboard')->with('success', 'Welcome back!');
               }else{
                 return redirect()->route('dashboard')->with('success', 'Welcome back!');
               }
             }
           }*/

        $pageConfigs = ['myLayout' => 'blank'];

        return view('auth.login', ['pageConfigs' => $pageConfigs]);
    }

    public function logout()
    {
        if (Cache::has('accessible_module_routes')) {
            Cache::forget('accessible_module_routes');
        }
        auth()->logout();

        return redirect('auth/login')->with('success', 'Successfully logged out');
    }

    public function verifyEmail()
    {
        if (auth()->user()->hasVerifiedEmail()) {
            return redirect('/')->with('success', 'Email already verified');
        }
        $pageConfigs = ['myLayout' => 'blank'];

        return view('auth.verify-email', ['pageConfigs' => $pageConfigs]);
    }
}
