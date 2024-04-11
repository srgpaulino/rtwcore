<?php

namespace App\Domain\API\Voucher;

use App\Domain\Request;
use DateTime;

class CreateRequest extends Request
{
    protected $structure = [
        "shop_id"               => "int",
        "account_id"            => "string",
        "prefix"                => "prefix",
        "num_vouchers"          => "int",
        "points"                => "int",
        "currency"              => "string",
        "value"                 => "int",
        "content_type_id"       => "int",
        "content_id"            => "int",
        "voucher_type"          => "int",
        "user_id"               => "int",
        "start_time"            => "dateTime",
        "points_start_time"     => "dateTime",
        "exp_time"              => "dateTime",
        "points_exp_time"       => "dateTime",
        "extra"                 => "optionalArray"
    ];

    protected function validatePrefix($prefix) : bool
    {
        return (
            (strlen($this->content['prefix']) >= 2) &&
            (strlen($this->content['prefix']) <= 4)
        );
    }

    protected function validateDateTime($date) : bool
    {
        return  (
            (date('Y-m-d H:i:s', strtotime($date)) !== false)
        );
    }

    protected function validateEndDateTime($endDateTime) : bool
    {
        return  (
            (date('Y-m-d H:i:s', strtotime($endDateTime)) !== false) &&
            (date('Y-m-d H:i:s', strtotime($this->content['start_time'])) <= date('Y-m-d H:i:s', strtotime($endDateTime)))
        );
    }

}
