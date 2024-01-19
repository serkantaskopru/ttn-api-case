<?php

namespace App\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Throwable;

class ApiResponseException extends HttpResponseException
{
    public function __construct($message, $statusCode, $code, Throwable $previous = null)
    {
        $response = new JsonResponse([
            'success' => $code == 200,
            'message' => $message,
            'code' => $statusCode,
            'status' => $code,
        ], 200);

        parent::__construct($response);
    }
}

