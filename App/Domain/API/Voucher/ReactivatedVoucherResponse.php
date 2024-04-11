<?php

namespace App\Domain\API\Voucher;

use Exception;

class ReactivatedVoucherResponse extends \App\Domain\ApiException
{

    protected $code = 201;
    protected $message = "Voucher has been reactivated.";

}