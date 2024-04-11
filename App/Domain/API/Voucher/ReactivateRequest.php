<?php

namespace App\Domain\API\Voucher;

use App\Domain\Request;

class ReactivateRequest extends Request
{
    protected $structure = [
        'code' => 'string'
    ];
}
