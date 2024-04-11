<?php

namespace App\Domain\API\Product;

use App\Domain\Request;

class RedeemRequest extends Request
{
    protected $structure = [
        "shop_id"       => "int",
        "end_user"      => "endUserAccount",
        "country"       => "string",
        "product_id"    => "string",
        "value"         => "double",
        "discount"      => "double"
    ];
}
