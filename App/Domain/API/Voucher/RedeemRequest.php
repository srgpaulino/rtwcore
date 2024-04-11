<?php

namespace App\Domain\API\Voucher;

use App\Domain\Request;

class RedeemRequest extends Request 
{

    protected $structure = [
        "code"      => "string",
        "shop_id"   => "int",
        "end_user"  => "string",
        "redeem"    => "int"
    ]; 

}