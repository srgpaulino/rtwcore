<?php

namespace App\Domain\TFCBlue\Orders;

use App\Domain\Request;

class IntentRequest extends Request
{

    protected $structure = [
        'amount' => 'amount',
        'currency' => 'currency'
    ];

}