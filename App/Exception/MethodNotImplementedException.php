<?php

namespace App\Exception;

use Exception;

class MethodNotImplementedException extends Exception
{
    public function __construct(string $message=null, Exception $previous=null)
    {
        if(null !== $message) {
            return parent::__construct($message, 501, $previous);
        }
        return parent::__construct("Requested method is not implemented", 501, $previous);
    }
}
