<?php

namespace App\Domain\API\Voucher;

use App\Domain\Request;

class CodeRequest extends Request
{
    protected $structure = [
        "code"               => "string",
        "format"             => "string"
    ];

}
