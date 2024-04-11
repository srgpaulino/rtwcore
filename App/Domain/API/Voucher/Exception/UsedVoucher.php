<?php

namespace App\Domain\API\Voucher\Exception;

use Exception;

class UsedVoucher extends \App\Domain\ApiException
{
    protected $code = 410;
    protected $message = "Voucher has been used";

}