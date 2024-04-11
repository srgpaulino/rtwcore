<?php

namespace App\Domain\Request;

use App\Domain\Request;

class VoucherDownloadRequest extends Request 
{

    private $structure = [
        'orderId'   => ''
    ];

}