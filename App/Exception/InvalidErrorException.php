<?php
namespace App\Exception;

use Exception;

class InvalidErrorException extends Exception
{
    public function __construct($message = "", $code =  500, Exception $previous=null)
    {
        if ($message === "") {
            $message = "Unable to retrieve internal error message";
        } 
        return parent::__construct($message, $code, $previous);
    }
}
