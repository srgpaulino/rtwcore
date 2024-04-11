<?php

namespace App\Provider\Id;

use Ramsey\Uuid\UuidFactory;

/**
 * Provides properly formated IDs
 **/
class UuidProvider implements IdProvider {

    private $uuidFactory;

    public function __construct(UuidFactory $uuidFactory)
    {
        $this->uuidFactory = $uuidFactory;
    }

    //Generate a unique ID
    public function generate() : string
    {
        return bin2hex($this->uuidFactory->uuid1()->getBytes());
    }

}
