<?php

namespace App\Domain\TFCBlue\Authentication;

use App\Domain\Request;

class LoginRequest extends Request 
{

    protected $structure = [
        'email'             => "email",
        'password'          => "password"
    ];

}