<?php

namespace App\Domain\API\Voucher;

use App\Domain\Request;

class DFRedeemRequest extends Request 
{

    protected $structure = [
        "voucherId" => "string",
        "extra" => "array",
        "action" => "string"
    ];

}