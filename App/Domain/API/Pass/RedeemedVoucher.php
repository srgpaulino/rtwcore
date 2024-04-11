<?php

namespace App\Domain\API\Pass;

use App\Domain\Domain;

class RedeemedPass extends Domain
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
        "userId"            => "userId",
    ];
}
