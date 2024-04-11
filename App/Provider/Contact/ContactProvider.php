<?php

namespace App\Provider\Contact;

class ContactProvider extends \App\Provider\SFContact\RealSFContactProvider {

    public function __construct(\TFCLog\TFCLogger $logger, \bjsmasth\Salesforce\CRUD $sfClient) {
        parent::__construct($logger, $sfClient);
    }

}
