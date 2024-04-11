<?php

namespace App\Domain\API\Voucher;

use App\Domain\Request;

class ListVoucherWalletRequest extends Request
{
    protected $structure = [
        "end_user" => "string"
    ];
}
