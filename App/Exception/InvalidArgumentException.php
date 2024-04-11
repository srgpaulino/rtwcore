<?php

namespace App\Exception;

use Exception;

class InvalidArgumentException extends Exception {

    public function __construct(string $message=null, Exception $previous=null)
    {
        if(null !== $message) {
            return parent::__construct($message, 400, $previous);
        }
        return parent::__construct("Invalid argument provided.", 400, $previous);
    }

}
