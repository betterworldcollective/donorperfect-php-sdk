<?php

namespace DonorPerfect\Exceptions;

use Exception;

class ObjectNotFoundException extends Exception
{
    public function __construct(string $message = 'Object not found')
    {
        parent::__construct($message, 404);
    }
}
