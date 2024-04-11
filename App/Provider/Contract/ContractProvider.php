<?php

namespace App\Provider\Contract;
use Slim\Collection;

class ContractProvider extends \App\Provider\SFContract\RealSFContractProvider {

    public function __construct(\TFCLog\TFCLogger $logger, \bjsmasth\Salesforce\CRUD $sfClient, Collection $settings) {
        parent::__construct($logger, $sfClient, $settings);
    }

}
