<?php

namespace App\Domain\Request;

use App\Domain\Request;

class ListOrderRequest extends Request 
{

    private $structure = [
        'orderId' => ''
    ];

}