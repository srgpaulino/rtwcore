<?php

namespace App\Domain\API\Product\Exception;


class ProductDoesNotExist extends \App\Domain\ApiException
{
    protected $code = 404;
    protected $message = "Product does not exist.";
}
