<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        if ($request->is('api/mobile/*')) {
            if ($exception instanceof AuthenticationException) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.', 'code' => 'unauthenticated'], 401);
            }

            if ($exception instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors(),
                ], 422);
            }

            if ($exception instanceof AuthorizationException) {
                return response()->json(['success' => false, 'message' => 'Forbidden.', 'code' => 'forbidden'], 403);
            }

            if ($exception instanceof ModelNotFoundException) {
                return response()->json(['success' => false, 'message' => 'Not found.', 'code' => 'not_found'], 404);
            }
        }

        if ($exception instanceof TokenMismatchException) {
            return redirect()->route('login')
                ->with('message', 'Your session expired due to inactivity. Please log in again.');
        }

        return parent::render($request, $exception);
    }
}
