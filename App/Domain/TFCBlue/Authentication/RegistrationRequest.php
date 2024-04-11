<?php

namespace App\Domain\TFCBlue\Authentication;

use App\Domain\Request;

class RegistrationRequest extends Request 
{

    protected $structure = [
        'name'              => 'string',
        'company'           => 'string',
        'email'             => 'email',
        'password'          => 'password',
        'repeatpassword'    => 'passwordconfirmation'
    ];

}