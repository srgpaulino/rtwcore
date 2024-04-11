<?php

namespace App\Domain\API\Product\Exception;

class ProviderError extends \App\Domain\ApiException
{
    protected $code = 410;
    protected $message = "Pass has expired.";
}
