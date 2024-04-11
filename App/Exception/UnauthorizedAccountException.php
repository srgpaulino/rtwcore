<?php

namespace App\Exception;

use Exception;

class UnauthorizedAccountException extends Exception {

    public function __construct(string $message=null, Exception $previous=null)
    {
        if(null !== $message) {
            return parent::__construct($message, 401, $previous);
        }
        return parent::__construct("Account not authorized to create product.", 401, $previous);
    }

}
