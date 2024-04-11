<?php

namespace App\Domain\API\Voucher\Exception;

use Exception;

class VoucherNotYetStarted extends \App\Domain\ApiException
{

    protected $code = 423;
    protected $message = "Voucher has not yet started.";

}