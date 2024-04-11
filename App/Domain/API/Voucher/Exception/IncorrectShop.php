<?php

namespace App\Domain\API\Voucher\Exception;

use Exception;

class IncorrectShop extends \App\Domain\ApiException
{

    protected $code = 409;
    protected $message = "Incorrect shop selected.";

}