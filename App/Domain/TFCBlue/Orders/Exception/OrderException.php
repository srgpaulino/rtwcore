<?php

namespace App\Domain\TFCBlue\Orders\Exception;

use Exception;

class OrderException extends Exception {

    public function __construct(string $message = null, Exception $previous = null) 
    {
        if (null !== $message) {
            return parent::__construct($message, 503, $previous);
        }
        return parent::__construct("Error while placing the Order", 503, $previous);
    }

}
