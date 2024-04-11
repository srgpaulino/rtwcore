<?php

namespace App\Domain\API\Product;

use App\Domain\Response;

class RedeemResponse extends Response
{
    protected $structure = [
        "product_id"    => "string",
        "end_user"      => "endUserAccount",
        "order_id"      => "string",
        "value"         => "int",
        "download"      => [
            "instructions"  => "string",
            "url"           => "url"
        ]       
    ];
}
