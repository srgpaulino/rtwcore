<?php

namespace App\Domain\TFCBlue\Orders;

use App\Domain\Request;

class VerifyingRequest extends Request
{

    protected $structure = [
        'intent_key' => 'intent_key',
        'status' => 'status'
    ];

}