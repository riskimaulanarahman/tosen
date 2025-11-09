<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use App\Services\AuditService;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Log all exceptions for audit trail
        $this->logException($exception, $request);

        // Handle specific exception types
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($exception, $request);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle API exceptions with proper JSON responses.
     */
    private function handleApiException(Throwable $exception, Request $request): JsonResponse
    {
        $status = 500;
        $message = 'An error occurred';
        $errors = [];
        $error_code = null;

        // Handle specific exception types
        switch (true) {
            case $exception instanceof ValidationException:
                $status = 422;
                $message = 'Validation failed';
                $errors = $exception->errors();
                $error_code = 'VALIDATION_ERROR';
                break;

            case $exception instanceof AuthenticationException:
                $status = 401;
                $message = 'Unauthenticated';
                $error_code = 'AUTHENTICATION_ERROR';
                break;

            case $exception instanceof AuthorizationException:
                $status = 403;
                $message = 'Unauthorized';
                $error_code = 'AUTHORIZATION_ERROR';
                break;

            case $exception instanceof ModelNotFoundException:
                $status = 404;
                $message = 'Resource not found';
                $error_code = 'NOT_FOUND';
                break;

            case $exception instanceof QueryException:
                if ($exception->getCode() == 23000) {
                    $status = 409;
                    $message = 'Data conflict. The resource already exists.';
                    $error_code = 'DUPLICATE_ENTRY';
                } else {
                    $status = 500;
                    $message = 'Database error occurred';
                    $error_code = 'DATABASE_ERROR';
                }
                break;

            default:
                $status = 500;
                $message = config('app.debug') ? $exception->getMessage() : 'Internal server error';
                $error_code = 'INTERNAL_ERROR';
                break;
        }

        // Add context information in development
        $context = [];
        if (config('app.debug')) {
            $context = [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        // Log security-related exceptions
        if (in_array($error_code, ['AUTHENTICATION_ERROR', 'AUTHORIZATION_ERROR', 'VALIDATION_ERROR'])) {
            AuditService::logSecurityEvent(
                'api_error_' . strtolower($error_code),
                "API Error: {$message}",
                'medium',
                array_merge($context, [
                    'request_path' => $request->path(),
                    'request_method' => $request->method(),
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                ])
            );
        }

        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $error_code,
            'errors' => $errors,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ], $status);
    }

    /**
     * Log exception for audit trail.
     */
    private function logException(Throwable $exception, Request $request): void
    {
        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'request_path' => $request->path(),
            'request_method' => $request->method(),
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Only log detailed information in production for security
        if (config('app.env') === 'production') {
            $context = array_intersect_key($context, array_flip([
                'exception', 'message', 'request_path', 'request_method', 'user_id', 'ip_address'
            ]));
        }

        logger()->error('Exception occurred', $context);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in to continue.',
                'error_code' => 'AUTHENTICATION_ERROR',
                'timestamp' => now()->toISOString(),
            ], 401);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * Convert a validation exception into a JSON response.
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'success' => false,
            'message' => 'The given data was invalid.',
            'error_code' => 'VALIDATION_ERROR',
            'errors' => $exception->errors(),
            'timestamp' => now()->toISOString(),
        ], 422);
    }
}
