<?php

namespace App\Domain;

class Client extends Domain 
{

    private $structure = [
        'id'                => null,
        'salesforceAccount' => null,
        'salesforceContact' => null,
        'cognitoAccount'    => null,
        'shop'              => null
    ];

}