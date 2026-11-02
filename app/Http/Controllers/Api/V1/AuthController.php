<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterFcmTokenRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Authenticate user and issue Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::with(['roles.permissions'])->where('phone', $request->phone)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->errorResponse(
                message: 'بيانات الدخول غير صحيحة، يرجى التأكد من رقم الجوال وكلمة المرور.',
                statusCode: Response::HTTP_UNAUTHORIZED,
                errorCode: 'INVALID_CREDENTIALS'
            );
        }

        if (! $user->is_active) {
            return $this->errorResponse(
                message: 'تم تعطيل هذا الحساب، يرجى مراجعة إدارة النظام.',
                statusCode: Response::HTTP_FORBIDDEN,
                errorCode: 'ACCOUNT_DEACTIVATED'
            );
        }

        // Generate token with device name
        $deviceName = $request->device_name ?? 'API Device';
        $token = $user->createToken($deviceName)->plainTextToken;

        // Record audit log for login
        AuditLog::record(
            action: 'auth.login',
            model: $user,
            oldValues: null,
            newValues: [
                'device_name' => $deviceName,
                'platform' => $request->platform ?? 'unknown',
            ],
            user: $user
        );

        return $this->successResponse(
            data: [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new UserResource($user),
            ],
            message: 'تم تسجيل الدخول بنجاح.'
        );
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles.permissions']);

        return $this->successResponse(
            data: new UserResource($user),
            message: 'تم استرجاع بيانات الملف الشخصي بنجاح.'
        );
    }

    /**
     * Revoke current access token (Logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke the current access token
        $request->user()->currentAccessToken()->delete();

        // Record audit log for logout
        AuditLog::record(
            action: 'auth.logout',
            model: $user,
            oldValues: null,
            newValues: null,
            user: $user
        );

        return $this->successResponse(
            data: null,
            message: 'تم تسجيل الخروج بنجاح.'
        );
    }

    /**
     * Register or update mobile FCM token.
     */
    public function registerFcmToken(RegisterFcmTokenRequest $request): JsonResponse
    {
        $user = $request->user();

        $deviceToken = UserDeviceToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_uuid' => $request->device_uuid,
            ],
            [
                'fcm_token' => $request->fcm_token,
                'platform' => $request->platform,
                'last_seen_at' => now(),
            ]
        );

        return $this->successResponse(
            data: [
                'device_uuid' => $deviceToken->device_uuid,
                'platform' => $deviceToken->platform,
                'last_seen_at' => $deviceToken->last_seen_at->toIso8601String(),
            ],
            message: 'تم تسجيل جهاز الهاتف لاستلام الإشعارات بنجاح.'
        );
    }
}
