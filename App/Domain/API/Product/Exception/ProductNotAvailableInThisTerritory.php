<?php

namespace App\Domain\API\Product\Exception;

class ProductNotAvailableInThisTerritory extends \App\Domain\ApiException
{
    protected $code = 404;
    protected $message = "Product not available in this territory.";
}
