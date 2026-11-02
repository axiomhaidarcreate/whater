<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    /**
     * Send a standardized success JSON response.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'تمت العملية بنجاح.',
        int $statusCode = Response::HTTP_OK,
        ?array $meta = null
    ): JsonResponse {
        $response = [
            'success' => true,
            'status_code' => $statusCode,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Send a standardized error JSON response.
     */
    protected function errorResponse(
        string $message,
        int $statusCode = Response::HTTP_BAD_REQUEST,
        ?string $errorCode = null,
        mixed $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'status_code' => $statusCode,
            'error_code' => $errorCode ?? 'OPERATION_FAILED',
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}
