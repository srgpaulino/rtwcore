<?php

namespace App\Exception;

use Exception;

class InvalidAuthenticationException extends Exception {

    public function __construct(string $message=null, Exception $previous=null)
    {
        if(null !== $message) {
            return parent::__construct($message, 401, $previous);
        }        
        return parent::__construct("Unable to authenticate user", 401, $previous);
    }

}
