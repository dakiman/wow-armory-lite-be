<?php

namespace App\Exceptions;

use Exception;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     */
    public function report(Throwable $exception): void
    {
        if ($exception instanceof ApiException) {
            \Log::error('API Exception', [
                'message' => $exception->getMessage(),
                'status_code' => $exception->getStatusCode(),
                'previous' => $exception->getPrevious()?->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        if ($exception instanceof BadResponseException) {
            \Log::error('External API Error', [
                'message' => $exception->getMessage(),
                'response' => $exception->hasResponse() ? (string) $exception->getResponse()->getBody() : null,
                'status_code' => $exception->hasResponse() ? $exception->getResponse()->getStatusCode() : null,
            ]);
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ApiException) {
            return response()->json([
                'error' => [
                    'message' => $exception->getMessage() ?? 'Unexpected error occurred.',
                    'status_code' => $exception->getStatusCode() ?? 500,
                ],
            ], $exception->getStatusCode() ?? 500);
        }

        if ($exception instanceof BadResponseException) {
            return response()->json([
                'error' => [
                    'message' => 'There was an error contacting external services',
                    'status_code' => 500,
                ],
            ], 500);
        }

        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response(['error' => 'You are not authenticated to access this resource.'], 401);
    }
}
