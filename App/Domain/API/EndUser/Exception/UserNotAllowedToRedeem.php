<?php

namespace App\Domain\API\EndUser\Exception;

use Exception;

class UserNotAllowedToRedeem extends \App\Domain\ApiException
{
    protected $code = 403;
    protected $message = "User not allowed to redeem.";
}
