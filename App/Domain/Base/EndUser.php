<?php

namespace App\Domain\EndUser;

class EndUser extends Domain 
{

    protected $structure = [
        'id'            => 'id'          ,
        'TFCUserId'     => 'TFCUserId'   ,
        'clientUserId'  => 'clientUserId',
        'loginName'     => 'loginName'   ,
        'name'          => 'name'        ,
        'lastName'      => 'lastName'    ,
        'coinsCount'    => 'coinsCount'  ,
        'geoId'         => 'geoId'       ,
        'language'      => 'language'    ,
        'email'         => 'email'       ,
        'shop'          => 'shop'        
    ];

}