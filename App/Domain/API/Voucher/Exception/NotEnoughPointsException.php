<?php

namespace App\Domain\API\Voucher\Exception;

use Exception;

class NotEnoughPointsException extends \App\Domain\ApiException 
{

    protected $code = 402;
    protected $message = "Voucher does not have enough points.";

}