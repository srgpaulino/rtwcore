<?php

namespace App\Domain\API\Voucher\Exception;

use Exception;

class VoucherPermission extends \App\Domain\ApiException
{

    protected $code = 401;
    protected $message = "Not allowed to use this voucher function";

}