<?php

namespace App\Domain;

use Exception;

class ApiException extends Exception 
{

    protected $code;
    protected $message;

    public function __construct(string $message = null, Exception $previous = null) 
    {
        if(null !== $message) {
            $this->message = $message;
        }
        return parent::__construct($this->message, $this->code, $previous);
    }

}
