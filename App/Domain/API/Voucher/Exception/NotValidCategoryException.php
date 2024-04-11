<?php

namespace App\Domain\API\Voucher\Exception;

use Exception;

class NotValidCategoryException extends \App\Domain\ApiException 
{

    protected $code = 405;
    protected $message = "Category is not valid.";

}