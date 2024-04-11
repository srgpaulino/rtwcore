<?php

namespace App\Domain\API\Voucher\Exception;

use Exception;

class IncorrectCountry extends \App\Domain\ApiException
{

    protected $code = 409;
    protected $message = "This voucher is not valid for this location.";

}