<?php

use App\Http\Middleware\PreserveActiveTab;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global web middleware: flash _active_tab from every form POST to session
        $middleware->web(append: [
            PreserveActiveTab::class,
        ]);
        $middleware->alias([
            'role'       => CheckRole::class,
            'permission' => CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'error_code' => 'VALIDATION_FAILED',
                    'message' => 'بيانات الإدخال غير صالحة، يرجى مراجعة الحقول المطلوبة.',
                    'errors' => $e->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status_code' => Response::HTTP_UNAUTHORIZED,
                    'error_code' => 'UNAUTHENTICATED',
                    'message' => 'جلسة العمل غير صالحة أو منتهية، يرجى تسجيل الدخول مجدداً.',
                ], Response::HTTP_UNAUTHORIZED);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status_code' => Response::HTTP_FORBIDDEN,
                    'error_code' => 'FORBIDDEN',
                    'message' => $e->getMessage() ?: 'ليس لديك الصلاحية الكافية لتنفيذ هذا الإجراء.',
                ], Response::HTTP_FORBIDDEN);
            }
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'error_code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'المورد المطلوب غير موجود أو تم حذفه.',
                ], Response::HTTP_NOT_FOUND);
            }
        });

        $exceptions->render(function (\App\Exceptions\InsufficientStockException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status_code' => Response::HTTP_BAD_REQUEST,
                    'error_code' => 'INSUFFICIENT_STOCK',
                    'message' => $e->getMessage() ?: 'الرصيد المخزني غير كافٍ لإتمام هذه العملية.',
                ], Response::HTTP_BAD_REQUEST);
            }
        });

        $exceptions->render(function (\InvalidArgumentException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status_code' => Response::HTTP_BAD_REQUEST,
                    'error_code' => 'INVALID_ARGUMENT',
                    'message' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status_code' => Response::HTTP_TOO_MANY_REQUESTS,
                    'error_code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'تم تجاوز الحد الأقصى للطلبات المسموح بها، يرجى المحاولة بعد قليل.',
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $isDebug = config('app.debug', false);

                return response()->json([
                    'success' => false,
                    'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'error_code' => 'SERVER_ERROR',
                    'message' => $isDebug ? $e->getMessage() : 'حدث خطأ غير متوقع في الخادم، تم تسجيل المشكلة للمعالجة.',
                    'details' => $isDebug ? [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ] : null,
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        });
    })->create();
