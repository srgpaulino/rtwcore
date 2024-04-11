<?php

namespace App\Domain\TFCBlue\Authentication;

use App\Domain\Request;

class ForgotPasswordRequest extends Request 
{

    private $structure = [
        'email' => 'email'
    ];

}