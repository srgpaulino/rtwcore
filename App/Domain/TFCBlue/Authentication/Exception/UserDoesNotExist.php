<?php

namespace App\Domain\TFCBlue\Authentication\Exception;

use Exception;

/**
 * 
 */
class UserDoesNotExist extends Exception {

    /**
     * 
     */
    public function __construct(string $message = null, Exception $previous = null) 
    {
        if (null !== $message) {
            return parent::__construct($message, 404, $previous);
        }
        return parent::__construct("This user does not exist", 404, $previous);
    }

}
