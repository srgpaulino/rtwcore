<?php

namespace App\Exception;

use Exception;

class InvalidLogException extends Exception {

    public function __construct(string $message=null, Exception $previous=null)
    {
        if(null !== $message) {
            return parent::__construct($message, 500, $previous);
        }
        return parent::__construct("Unable to instantiate log", 500, $previous);
    }

}
