<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        $this->renderable(function (Throwable $e) {
            if($e instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geçersiz URL',
                    'code' => 10404,
                    'status' => Response::HTTP_NOT_FOUND,
                ], Response::HTTP_OK);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 10500,
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ], Response::HTTP_OK);
        });
        /*$this->reportable(function (Throwable $e) {
            //
        });*/
    }
}
