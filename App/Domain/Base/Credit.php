<?php

namespace App\Domain;

class Credit extends Domain 
{

    private $structure = [
        'account'       => null,
        'credit_date'   => null,
        'value'         => null,
        'currency'      => null
    ];

}