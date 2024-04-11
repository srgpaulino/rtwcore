<?php

namespace App\Domain\API\Product\Exception;


class InsufficientCreditException extends \App\Domain\ApiException
{
    protected $code = 409;
    protected $message = "Insufficient points on your account.";
}
