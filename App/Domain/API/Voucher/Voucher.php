<?php

namespace App\Domain\API\Voucher;

use App\Domain\Domain;

class Voucher extends Domain
{
    protected $structure = [
        "voucher_id"        => "voucher_id",
        "shop_id"           => "shop_id",
        "account_id"        => "account_id",
        "code"              => "code",
        "points"            => "points",
        "content_type_id"   => "content_type_id",
        "content_id"        => "content_id",
        "voucher_type"      => "voucher_type",
        "user_id"           => "user_id",
        "status"            => "status",
        "gen_time"          => "gen_time",
        "session_id"        => "session_id",
        "use_time"          => "use_time",
        "generator_id"      => "generator_id",
        "exp_time"          => "exp_time",
        "start_time"        => "start_time",
        "points_exp_time"   => "points_exp_time",
        "points_start_time" => "points_start_time",
        "end_user_id"       => "end_user_id",
        "pass_id"           => "pass_id"
    ];
}
