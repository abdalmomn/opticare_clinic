<?php

namespace App\Modules\Core\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success'     => true,
            'status_code' => $statusCode,
            'message'     => $message,
            'data'        => $data,
        ];

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    public static function created(
        mixed $data = null,
        string $message = 'Created successfully',
        array $meta = []
    ): JsonResponse {
        return self::success(
            data: $data,
            message: $message,
            statusCode: 201,
            meta: $meta
        );
    }

    public static function noContent(
        string $message = 'No content'
    ): JsonResponse {
        return self::success(
            data: null,
            message: $message,
            statusCode: 204
        );
    }

    public static function error(
        string $message = 'Something went wrong',
        int $statusCode = 500,
        ?string $code = null,
        mixed $errors = null
    ): JsonResponse {
        $response = [
            'success'     => false,
            'status_code' => $statusCode,
            'message'     => $message,
            'code'        => $code,
            'errors'      => $errors,
        ];

        return response()->json($response, $statusCode);
    }

    public static function validationError(
        mixed $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: 422,
            code: 'VALIDATION_ERROR',
            errors: $errors
        );
    }

    public static function unauthorized(
        string $message = 'Unauthenticated'
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: 401,
            code: 'UNAUTHORIZED'
        );
    }

    public static function forbidden(
        string $message = 'This action is unauthorized'
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: 403,
            code: 'FORBIDDEN'
        );
    }

    public static function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: 404,
            code: 'NOT_FOUND'
        );
    }

    public static function serverError(
        string $message = 'Internal server error'
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: 500,
            code: 'SERVER_ERROR'
        );
    }


    public static function handleException(\Throwable $e): \Illuminate\Http\JsonResponse
{
    if ($e instanceof \Illuminate\Auth\AuthenticationException) {
        return self::unauthorized('Unauthenticated. Please login first.');
    }

    if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
        return self::forbidden('You are not authorized to perform this action.');
    }

    if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
        return self::notFound('The requested resource was not found.');
    }

    if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
        return self::error(
            message:    $e->getMessage() ?: 'An error occurred.',
            statusCode: $e->getStatusCode(),
        );
    }

    if ($e instanceof \Illuminate\Validation\ValidationException) {
        return self::validationError(
            errors:  $e->errors(),
            message: 'Validation failed.'
        );
    }

    $message = app()->environment('production')
        ? 'Internal server error.'
        : $e->getMessage();

    return self::serverError($message);
}
}
