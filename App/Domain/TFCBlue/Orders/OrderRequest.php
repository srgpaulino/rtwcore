<?php

namespace App\Domain\Request;

use App\Domain\Request;

class OrderRequest extends Request 
{

    private $structure = [
        'user' => '', //cognito user id
        'baseOrder' => [
            'numRewards'        => '',
            'currency'          => '',
            'valuePerReward'    => [
                'points'    => '',
                'currency'  => ''
            ],
            'total'        => [
                'points'    => '',
                'currency'  => ''
            ]
        ],
        'voucherValidity' => [
            'startDate'         => '',
            'expirationDate'    => '',
            'timeZone'          => ''
        ],
        'payment' => [
            'stripeToken' => [
                "id"=> "tok_1EKjoxHLB5V2Z4JKdlTs0Wc7",
                "object"=> "token",
                "card"=> [
                  "id"=> "card_1EKjoxHLB5V2Z4JKOH0EmHgc",
                  "object"=> "card",
                  "address_city"=> null,
                  "address_country"=> null,
                  "address_line1"=> null,
                  "address_line1_check"=> null,
                  "address_line2"=> null,
                  "address_state"=> null,
                  "address_zip"=> "42424",
                  "address_zip_check"=> "unchecked",
                  "brand"=> "Visa",
                  "country"=> "US",
                  "cvc_check"=> "unchecked",
                  "dynamic_last4"=> null,
                  "exp_month"=> 4,
                  "exp_year"=> 2024,
                  "funding"=> "credit",
                  "last4"=> "4242",
                  "metadata"=> [],
                  "name"=> "Yolanda",
                  "tokenization_method"=> null
                  ],
                "client_ip"=> "172.111.154.130",
                "created"=> 1554200783,
                "livemode"=> false,
                "type"=> "card",
                "used"=> false
                  ],
              "amount"=> 5,
              "email"=> "anasmith@gmail.com",
              "id"=> 1
            ],
    ];

}