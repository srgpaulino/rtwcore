<?php

namespace App\Domain;

class Account extends Domain 
{

    private $structure = [
        'id'                    => null,
        'owner'                 => null,
        'name'                  => null,
        'type'                  => null,
        'phone'                 => null,
        'website'               => null,
        'accountId'             => null,
        'clientShop'            => null,
        'marketSector'          => null,
        'billingAddress'        => null,
        'VAT'                   => null,
        'companyRegistration'   => null,
        'sicDescription'        => null
    ];

}