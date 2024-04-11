<?php

namespace App\Domain\API\Pass\Exception;

use Exception;

class PassNotYetStarted extends \App\Domain\ApiException
{
    protected $code = 423;
    protected $message = "Pass has not yet started.";
}
