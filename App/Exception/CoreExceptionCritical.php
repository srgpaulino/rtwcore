<?php

namespace App\Exception;

use Exception;

class CoreExceptionCritical extends Exception
{
    public function __construct(string $message=null, Exception $previous=null)
    {
        
        if(null !== $message) 
        {
            return parent::__construct($message, 404, $previous);
        }
        return parent::__construct("Requested data or object does not exist.", 404, $previous);
    }
}
