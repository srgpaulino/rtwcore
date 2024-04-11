<?php

namespace App\Domain\API\Voucher;

use App\Domain\Request;

class DFActivateRequest extends Request 
{

    protected $structure = [
        "voucher" => "string",
        "endUser" => "string",
        "data" => "array"
    ];

}