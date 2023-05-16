<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ApiException extends Exception {
    protected int $statusCode;

    public function __construct($message = "Something went wrong...", Throwable $previous = null, $statusCode = 500)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
