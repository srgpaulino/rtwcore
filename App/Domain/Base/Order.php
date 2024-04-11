<?php

namespace App\Domain;

class Order extends Domain 
{

    private $structure = [
        'number'        => null,
        'value'         => null,
        'currency'      => null,
        'owner'         => null,
        'quantity'      => null,
        'creation_date' => null,
        'account_name'  => null,
        'account_id'    => null,
        'paid'          => null
    ];

}