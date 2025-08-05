<?php

namespace DonorPerfect\Exceptions;

use Exception;

class BadRequestException extends Exception
{
    public function __construct(array $response = [])
    {
        $message = $response['message'] ?? 'Bad request';
        parent::__construct($message, 400);
    }
}
