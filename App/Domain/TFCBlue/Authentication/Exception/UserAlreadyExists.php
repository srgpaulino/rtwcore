<?php

namespace App\Domain\TFCBlue\Authentication\Exception;

use Exception;

class UserAlreadyExists extends Exception {

    public function __construct(string $message = null, Exception $previous = null) 
    {
        if (null !== $message) {
            return parent::__construct($message, 422, $previous);
        }
        return parent::__construct("This email is already registered", 422, $previous);
    }

}
