<?php

namespace App\Provider\Account;

class AccountProvider extends \App\Provider\SFAccount\RealSFAccountProvider{
    
    public function __construct(\TFCLog\TFCLogger $logger, \bjsmasth\Salesforce\CRUD $sfClient) {
//        echo "I am in account provider";die;
        parent::__construct($logger, $sfClient);
    }
    
}
