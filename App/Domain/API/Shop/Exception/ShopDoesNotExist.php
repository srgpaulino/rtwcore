<?php

namespace App\Domain\API\Shop\Exception;

use Exception;

class ShopDoesNotExist extends \App\Domain\ApiException
{
    protected $code = 404;
    protected $message = "Shop does not exist.";
}
