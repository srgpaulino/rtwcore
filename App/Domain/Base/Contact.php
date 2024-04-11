<?php

namespace App\Domain;

class Contact extends Domain 
{

    private $structure = [
        'id'                    => null,
        'owner'                 => null,
        'account'               => null,
        'firstName'             => null,
        'lastName'              => null,
        'email'                 => null,
        'phone'                 => null,
        'title'                 => null,
        'currency'              => null,
        'country'               => null,
        'mailingAddress'        => null,
        'shop'                  => null,
        'emailOptOut'           => null,
        'clientOf'              => null
    ];

}