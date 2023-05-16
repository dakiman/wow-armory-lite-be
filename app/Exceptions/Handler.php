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

    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    public function render($request, Throwable $exception)
    {
        if($exception instanceof ApiException) {
            return response()->json([
                'message' => $exception->getMessage() ?? 'Unexpected error occured.',
            ], $exception->getStatusCode() ?? 500);
        }

        if($exception instanceof BadResponseException) {
            return response()->json([
               'message' => 'There was an error contacting external services'
            ], 500);
        }

        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response(['error' => 'You are not authenticated to access this resource.'], 401);
    }
}
