<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse('غير مصرح لك بالوصول، يرجى تسجيل الدخول أولاً.', Response::HTTP_UNAUTHORIZED, 'UNAUTHORIZED');
        }

        if (! $user->is_active) {
            return $this->errorResponse('تم تعطيل حسابك، يرجى التواصل مع الإدارة.', Response::HTTP_FORBIDDEN, 'ACCOUNT_DEACTIVATED');
        }

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        if (! $user->hasAnyRole($roles)) {
            return $this->errorResponse('ليس لديك الصلاحية الكافية للوصول إلى هذا المورد.', Response::HTTP_FORBIDDEN, 'FORBIDDEN');
        }

        return $next($request);
    }
}
