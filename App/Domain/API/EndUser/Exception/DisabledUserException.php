<?php

namespace App\Domain\API\EndUser\Exception;

use Exception;

class DisabledUserException extends \App\Domain\ApiException
{
    protected $code = 403;
    protected $message = "User is disabled.";
}
