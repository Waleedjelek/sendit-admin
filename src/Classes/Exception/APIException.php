<?php

namespace App\Classes\Exception;

use Symfony\Component\HttpFoundation\Response;

class APIException extends BaseException
{
    protected int $responseCode;

    public function __construct(string $message, int $code, int $responseCode = Response::HTTP_BAD_REQUEST, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->responseCode = $responseCode;
    }

    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }
}
