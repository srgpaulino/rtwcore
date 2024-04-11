<?php

namespace App\Domain\API\Voucher;

use App\Domain\Request;

class InvalidateRequest extends Request
{
    protected $structure = [
        "code"      => "string"
    ];
}
