<?php

namespace App\Domain\TFCBlue\Authentication\Exception;

use Exception;

class CognitoLoginException extends Exception {

    public function __construct(string $message = null, Exception $previous = null) 
    {
        if (null !== $message) {
            return parent::__construct($message, 422, $previous);
        }
        return parent::__construct("Could not log cognito login into database", 422, $previous);
    }

}
