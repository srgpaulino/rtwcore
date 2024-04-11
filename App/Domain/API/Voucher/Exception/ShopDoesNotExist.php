<?php

namespace App\Domain\API\Voucher\Exception;

use Exception;

class ShopDoesNotExist extends \App\Domain\ApiException
{

    protected $code = 404;
    protected $message = "Shop does not exist";

}