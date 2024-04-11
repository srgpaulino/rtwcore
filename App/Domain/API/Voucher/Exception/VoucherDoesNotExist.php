<?php

namespace App\Domain\API\Voucher\Exception;

use Exception;

class VoucherDoesNotExist extends \App\Domain\ApiException
{

    protected $code = 404;
    protected $message = "Voucher does not exist";

}