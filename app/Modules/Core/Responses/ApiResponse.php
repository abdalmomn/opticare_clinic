<?php

namespace App\Modules\Core\Responses;

use Illuminate\Http\JsonResponse;
use App\Modules\Core\Exceptions\ApiException;
class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ):JsonResponse
    {
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

    public static function created(mixed $data = null, string $message = 'created successfully', array $meta = []):JsonResponse
    {
        return self::success(
            data: $data,
            message: $message,
            statusCode: 201,
            meta: $meta
        );
    }

    public static function noContent(string $message = 'no content'):JsonResponse
    {
        return self::success(
            data: null,
            message: $message,
            statusCode: 204
        );
    }

    public static function error(
        string $message = 'something went wrong',
        int $statusCode = 500,
        ?string $code = null,
        mixed $errors = null
    ):JsonResponse
    {
        $response = [
            'success'     => false,
            'status_code' => $statusCode,
            'message'     => $message,
            'code'        => $code,
            'errors'      => $errors,
        ];
        return response()->json($response, $statusCode);
    }

    public static function validationError(mixed $errors, string $message = 'validation failed'):JsonResponse
    {
        return self::error(
            message: $message,
            statusCode: 422,
            code: 'VALIDATION_ERROR',
            errors: $errors
        );
    }

    public static function unauthorized(string $message = 'unauthenticated'):JsonResponse
    {
        return self::error(
            message: $message,
            statusCode: 401,
            code: 'UNAUTHORIZED'
        );
    }

    public static function forbidden(string $message = 'this action is unauthorized'):JsonResponse
    {
        return self::error(
            message: $message,
            statusCode: 403,
            code: 'FORBIDDEN'
        );
    }

    public static function notFound(string $message = 'resource not found'):JsonResponse
    {
        return self::error(
            message: $message,
            statusCode: 404,
            code: 'NOT_FOUND'
        );
    }

    public static function serverError(string $message = 'internal server error'):JsonResponse
    {
        return self::error(
            message: $message,
            statusCode: 500,
            code: 'SERVER_ERROR'
        );
    }


    public static function handleException(\Throwable $e): \Illuminate\Http\JsonResponse
{
    if ($e instanceof \Illuminate\Auth\AuthenticationException)
    {
    return self::unauthorized(__('auth.errors.unauthenticated'));
    }
    if ($e instanceof \Illuminate\Auth\Access\AuthorizationException)
    {
        return self::forbidden(__('auth.errors.forbidden'));
    }
    if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException)
    {
        return self::notFound(__('auth.errors.not_found'));
    }
    if ($e instanceof ApiException)
    {
        return self::error(
            message: $e->getMessage() ?: __('auth.errors.generic_error'),
            statusCode: $e->getStatusCode(),
            code: $e->getErrorCode(),
            errors: $e->getErrors()
        );
    }
    if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException)
    {
        return self::error(
            message: $e->getMessage() ?: __('auth.errors.generic_error'),
            statusCode: $e->getStatusCode(),
        );
    }
    if ($e instanceof \Illuminate\Validation\ValidationException)
    {
        return self::validationError(
            errors:  $e->errors(),
            message: __('auth.validation.failed')
        );
    }
    $message = app()->environment('production')
        ? __('auth.errors.server_error')
        : $e->getMessage();

    return self::serverError($message);
}
}
