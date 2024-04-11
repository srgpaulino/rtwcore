<?php

namespace App\Domain\API\Pass;

use App\Domain\Domain;

class Pass extends Domain
{
    protected $structure = [
        "account_id"        => "account_id",
        "code"              => "code",
        "content_type_id"   => "content_type_id",
        "user_id"           => "user_id",
        "start_time"        => "start_time",
        "exp_time"          => "exp_time",
        "duration"          => "duration"
    ];
}
