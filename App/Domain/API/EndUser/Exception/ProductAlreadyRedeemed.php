<?php

namespace App\Domain\API\EndUser\Exception;

use Exception;

class ProductAlreadyRedeemed extends \App\Domain\ApiException
{
    protected $code = 410;
    protected $message = "Pass has expired.";
}
