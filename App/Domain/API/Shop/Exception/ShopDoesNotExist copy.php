<?php

namespace App\Domain\API\Shop\Exception;

use Exception;

class DisabledShopException extends \App\Domain\ApiException
{
    protected $code = 403;
    protected $message = "Shop is disabled.";
}
