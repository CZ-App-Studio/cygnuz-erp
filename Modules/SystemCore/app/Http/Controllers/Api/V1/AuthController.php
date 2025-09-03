<?php

namespace Modules\SystemCore\app\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\SystemCore\app\Http\Controllers\Api\BaseApiController;
use Modules\SystemCore\app\Http\Resources\UserResource;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseApiController
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'device_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return $this->unauthorizedResponse('Invalid email or password');
            }

            $user = Auth::user();
            $user->load(['roles']);

            // Check if user is active (handle enum values)
            $statusValue = is_object($user->status) && property_exists($user->status, 'value')
              ? $user->status->value
              : $user->status;

            if ($statusValue !== 'active') {
                return $this->forbiddenResponse('Your account is not active. Please contact administrator.');
            }

            // Update device token if provided (commented until column is added)
            // if ($request->has('device_token')) {
            //     $user->device_token = $request->device_token;
            //     $user->save();
            // }

            return $this->successResponse([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => new UserResource($user),
            ], 'Login successful');

        } catch (JWTException $e) {
            return $this->errorResponse('Could not create token', null, 500);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return $this->successResponse(null, 'Logout successful');
        } catch (JWTException $e) {
            return $this->errorResponse('Failed to logout', null, 500);
        }
    }

    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());

            return $this->successResponse([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ], 'Token refreshed');
        } catch (JWTException $e) {
            return $this->unauthorizedResponse('Could not refresh token');
        }
    }

    public function me(): JsonResponse
    {
        $user = Auth::user();
        $user->load(['designation', 'department', 'shift', 'team', 'reportingTo', 'roles']);

        return $this->successResponse(
            new UserResource($user),
            'User profile retrieved'
        );
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Current password is incorrect');
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->successResponse(null, 'Password changed successfully');
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // TODO: Implement password reset email logic
        // For now, return success response
        return $this->successResponse(
            null,
            'If your email exists in our system, you will receive a password reset link'
        );
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // TODO: Implement password reset with token logic
        // For now, return success response
        return $this->successResponse(null, 'Password reset successful');
    }
}
