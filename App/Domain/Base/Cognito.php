<?php

namespace App\Domain;

class Cognito extends Domain 
{

    private $structure = [
        'username'      => null,
        'name'          => null,
        'email'         => null,
        'shopid'        => null,
        'sfcontactid'   => null,
        'sfaccountid'   => null,
        'status'        => null
    ];

}