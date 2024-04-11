<?php

namespace App\Repository;

use tfc\DynamoLogger as TFCLogger;
use Ramsey\Uuid\Uuid;

class LogRepository
{

    private $logger;

    public function __construct(TFCLogger $logger)
    {
        $this->logger = $logger;
    } 

    public function read(Uuid $id)
    {
        //ToDo
    }

    public function list()
    {
        //ToDo
    }

}