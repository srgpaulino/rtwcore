<?php

namespace App\Repository;

use App\Provider\Provider;

class Repository {

    protected $_providers;

    public function __construct(Array $providers) {
        $this->_providers = $providers;
    }

}