<?php

namespace App\Domain\Response;

use App\Domain\Response;

class LoginResponse extends Response 
{

    private $structure = [
        'name' => 'name',
        'email' => 'email',
        'cognitoId' => 'cognitoId',
        'sfAccountId' => 'sfAccountId',
        'sfContactId' => 'sfContactId',
        'sessionToken' => 'sessionToken'
    ];

}