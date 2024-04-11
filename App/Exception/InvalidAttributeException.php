<?php

namespace App\Exception;

use Exception;

class InvalidAttributeException extends Exception
{
    public function __construct(string $message=null, Exception $previous=null)
    {
        if (null !== $message) {
            return parent::__construct($message, 415, $previous);
        }
        return parent::__construct("Unable to reach attribute: " . parent::getTraceAsString(), 415, $previous);
    }
}
