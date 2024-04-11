<?php

namespace App\Domain\API\Pass\Exception;

use Exception;

class ExpiredPass extends \App\Domain\ApiException
{
    protected $code = 410;
    protected $message = "Pass has expired.";
}
