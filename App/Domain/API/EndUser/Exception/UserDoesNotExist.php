<?php

namespace App\Domain\API\EndUser\Exception;

use Exception;

class UserDoesNotExist extends \App\Domain\ApiException
{
    protected $code = 404;
    protected $message = "User does not exist.";
}
