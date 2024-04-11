<?php

namespace App\Domain\TFCBlue\Authentication\Exception;

use Exception;

class SFNotFound extends Exception 
{

    public function __construct(string $message, Exception $previous = null) 
    {
        return parent::__construct($message, 404, $previous);
    }

}
