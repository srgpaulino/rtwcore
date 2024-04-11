<?php

namespace App\Domain\API\Product;

use App\Domain\Domain;

class Game extends Domain
{

    protected $structure = [
        "order_id"          => "string",
        "product_name"      => "string",
        "download_url"      => "url",
        "user"              => "string",
        "point_cost"        => "int",
        "remaining_points"  => "int"
    ];  

}