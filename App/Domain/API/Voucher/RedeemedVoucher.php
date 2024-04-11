<?php

namespace App\Domain\API\Voucher;

use App\Domain\Domain;

class RedeemedVoucher extends Domain
{

    protected $structure = [
        "code"              => "code",
        "points"            => "points",
        "contentType"       => "contentType",
        "voucherType"       => "voucherType",
        "useTime"           => "useTime",
        "shopId"            => "shopId",
        "pointsStartTime"   => "pointsStartTime",
        "pointsExpTime"     => "pointsExpTime",
        "userId"            => "userId"
    ];

}