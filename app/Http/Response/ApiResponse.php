<?php

namespace App\Http\Response;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiResponse implements Responsable
{
    public static int $error = Response::HTTP_INTERNAL_SERVER_ERROR;
    public static int $success = Response::HTTP_OK;
    public static int $unprocessable = Response::HTTP_UNPROCESSABLE_ENTITY;
    private string|array $message;
    private int $statusCode = 0;
    private ?Throwable $exception = null;
    private int $code = Response::HTTP_INTERNAL_SERVER_ERROR;
    private array $headers = [];

    public function __construct(
        string|array $message,
        int $statusCode = 0,
        int $code = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?Throwable $exception = null,
        array $headers = []
    ) {
        $this->headers = $headers;
        $this->statusCode = $statusCode;
        $this->code = $code;
        $this->exception = $exception;
        $this->message = $message;
    }

    /**
     * @param $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $response = [
            'success' => $this->code == 200,
            'message' => $this->message,
            'code' => $this->statusCode,
            'status' => $this->code
        ];

        if (!is_null($this->exception) && config('app.debug')) {
            $response['debug'] = [
                'message' => $this->exception->getMessage(),
                'file'    => $this->exception->getFile(),
                'line'    => $this->exception->getLine(),
                'trace'   => $this->exception->getTraceAsString()
            ];
        }

        return new JsonResponse($response, $this->code, $this->headers);
    }
}
