<?php

namespace App\Domain\API\Product\Exception;

class WrongProductPrice extends \App\Domain\ApiException
{
    protected $code = 409;
    protected $message = "Wrong product price.";
}
