<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\Response;

class FastApiException extends Exception
{
    public function __construct(
        string $message,
        int $statusCode,
        public readonly Response $response,
    ) {
        parent::__construct($message, $statusCode);
    }
}
