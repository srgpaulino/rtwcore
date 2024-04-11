<?php

namespace App\Domain\API\Voucher;

use App\Domain\Request;

class VoucherWalletRequest extends Request
{
    protected $structure = [
        "code" => "string"
    ];
}
