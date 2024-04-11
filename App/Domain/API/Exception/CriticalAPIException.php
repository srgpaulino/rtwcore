<?php

namespace App\Domain\API\Exception;

use Exception;

class CriticalAPIException extends Exception
{
    protected $code = 500;
    protected $message = "Critical Error.";
}
