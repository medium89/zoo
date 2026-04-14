<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if (! $this->isSessionExpiryException($e)) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Page expired, please refresh and try again.',
                ], 419);
            }

            $message = 'Сессия истекла. Обновите страницу и войдите снова.';
            $input = $request->except(['password', 'password_confirmation']);

            if ($request->routeIs('login') || $request->is('zooadmin*')) {
                return redirect()
                    ->guest(route('login'))
                    ->withInput($input)
                    ->withErrors(['session' => $message]);
            }

            return back()
                ->withInput($input)
                ->withErrors(['session' => $message]);
        });
    }

    protected function isSessionExpiryException(Throwable $e): bool
    {
        if ($e instanceof TokenMismatchException) {
            return true;
        }

        return $e instanceof HttpExceptionInterface && $e->getStatusCode() === 419;
    }
}
