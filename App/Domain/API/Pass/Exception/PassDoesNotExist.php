<?php

namespace App\Domain\API\Pass\Exception;

use Exception;

class PassDoesNotExist extends \App\Domain\ApiException
{
    protected $code = 404;
    protected $message = "Pass does not exist";
}
