<?php

namespace App\Domain\API\Pass;

use App\Domain\Domain;

class RedeemedPass extends Domain
{
    protected $structure = [
        "code"              => "code",
        "content_type_id"   => "content_type_id",
        "user_id"           => "user_id",
        "activation_date"   => "activation_date",
        "expiration_date"   => "expiration_date",
        "duration"          => "duration",
        "vouchers"          => "vouchers"
    ];
}
