<?php

namespace App\Domain\API\Pass\Exception;

use Exception;

class IncorrectShop extends \App\Domain\ApiException {

    protected $code = 409;
    protected $message = "Incorrect shop selected.";

}