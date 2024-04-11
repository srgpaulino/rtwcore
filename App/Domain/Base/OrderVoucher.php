<?php

namespace App\Domain;

class OrderVoucher extends Domain 
{

    private $structure = [
        'clubcoin_value'        => null,
        'created_by'            => null,
        'currency'              => null,
        'start_date'            => null,
        'expiry_date'           => null,
        'order'                 => null,
        'voucher_code'          => null,
        'total_amount'          => null,
        'points_expiry_date'    => null,
        'end_user'              => null,
        'account'               => null,
        'shop'                  => null,
        'used'                  => null
    ];

}