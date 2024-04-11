<?php

namespace App\Domain\API\Voucher;

use Exception;

class InvalidatedVoucherResponse extends \App\Domain\ApiException
{

    protected $code = 201;
    protected $message = "Voucher has been invalidated.";

}