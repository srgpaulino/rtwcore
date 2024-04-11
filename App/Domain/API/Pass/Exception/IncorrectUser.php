<?php

namespace App\Domain\API\Pass\Exception;

use Exception;

class IncorrectUser extends \App\Domain\ApiException {

    protected $code = 409;
    protected $message = "Incorrect user attempting to redeem pass.";

}