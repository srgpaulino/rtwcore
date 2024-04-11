<?php

namespace App\Domain\API\EndUSer\Exception;

use Exception;

class NotEnoughPoints extends \App\Domain\ApiException
{
    protected $code = 410;
    protected $message = "Pass has expired.";
}
