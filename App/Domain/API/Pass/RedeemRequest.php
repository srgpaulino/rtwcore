<?php

namespace App\Domain\API\Pass;

use App\Domain\Request;

class RedeemRequest extends Request
{
    protected $structure = [
        "code"      => "string",
        "end_user"  => "endUserAccount",
        "shop_id"   => "int",
        "redeem"    => "int"
    ];
}
