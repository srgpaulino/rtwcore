<?php

namespace App\Domain;

class OrderItem extends Domain 
{

    private $structure = [
        'item'          => null,
        'start_date'    => null,
        'end_date'      => null,
        'order'         => null,
        'unit_price'    => null,
        'created_by'    => null,
        'clubcoins'     => null,
        'account'       => null,
        'contact'       => null
    ];

}