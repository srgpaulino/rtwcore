<?php

namespace App\Domain\API\Pass;

use App\Domain\Request;
use DateTime;

class CreateRequest extends Request
{
    protected $structure = [
        "shop_id"           => "int",
        "prefix"            => "prefix",
        "account_id"        => "sfAccount",
        "contact"           => "email",
        "num_passes"        => "int",
        "content_type_id"   => "int",
        "end_user"          => "endUserAccount",
        "start_date"        => "startDate",
        "exp_date"          => "endDate",
        "duration"          => "positiveInt",
        "num_vouchers"      => "int",
        "extra"             => "optionalArray"
    ];

    protected function validatePrefix($prefix) : bool
    {
        return (
            (strlen($this->content['prefix']) >= 2) &&
            (strlen($this->content['prefix']) <= 4)
        );
    }

    protected function validateStartDate($date) : bool
    {
        return  (
            (date('Y-m-d', strtotime($date)) !== false)
        );
    }

    protected function validateEndDate($endDate) : bool
    {
        return  (
            (date('Y-m-d', strtotime($endDate)) !== false) &&
            (date('Y-m-d', strtotime($this->content['start_date'])) <= date('Y-m-d', strtotime($endDate)))
        );
    }
}
