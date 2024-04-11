<?php

namespace App\Domain\API\Voucher;

use App\Domain\Request;

class ListRequest extends Request
{
    protected $structure = [
        'where' => 'array'
    ];
}
