<?php

namespace App\Domain\API\Voucher\Exception;

use Exception;

class NoVouchersAvailable extends \App\Domain\ApiException
{

    protected $code = 404;
    protected $message = "No vouchers for this user";

}