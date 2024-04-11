<?php

namespace App\Domain;

class Shop extends Domain 
{

    private $structure = [
        "id" => null,
        "name" => null,
        "url" => null,
        "email" => null,
        "abbreviation" =>  null,
        "type_id" => null,
        "enabled" => null,
        "is_default" => null,
        "is_connected" => null,
        "account_id" => null 
    ];

}