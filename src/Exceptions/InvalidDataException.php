<?php

namespace DonorPerfect\Exceptions;

use Exception;

class InvalidDataException extends Exception
{
    public function __construct(string $message = 'Invalid data')
    {
        parent::__construct($message, 422);
    }
}
