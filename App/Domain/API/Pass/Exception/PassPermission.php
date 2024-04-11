<?php

namespace App\Domain\API\Pass\Exception;

use Exception;

class PassPermission extends \App\Domain\ApiException
{
    protected $code = 401;
    protected $message = "Not allowed to use this pass function";
}
