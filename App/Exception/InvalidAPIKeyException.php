<?php

namespace App\Exception;

use Exception;

class InvalidAPIKeyException extends Exception
{
    public function __construct(string $message=null, Exception $previous=null)
    {
        
        if(null !== $message) 
        {
            return parent::__construct($message, 403, $previous);
        }
        return parent::__construct("API key mismatch.", 403, $previous);
    }
}
