<?php

namespace App\Domain;

class Voucher extends Domain 
{

    private $structure = [
        'voucher_id'        => null,
        'shop_id'           => null,
        'account_id'        => null,
        'code'              => null,
        'points'            => null,
        'content_type_id'   => null,
        'voucher_type'      => null,
        'user_id'           => null,
        'status'            => null,
        'gen_time'          => null,
        'session_id'        => null,
        'use_time'          => null,
        'generator_id'      => null,
        'exp_time'          => null,
        'start_time'        => null,
        'points_exp_time'   => null,
        'points_start_time' => null,
        'api_user_id'       => null,
        'timestamp'         => null,
        'signature'         => null
    ];

}