<?php

namespace App\Domain\TFCBlue\Authentication;

use App\Domain\Request;

class LogoutRequest extends Request 
{

    protected $structure = [
        'cognitoId' => ''
    ];

}