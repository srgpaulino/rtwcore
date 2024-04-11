<?php

namespace App\Provider\APIUser;

interface APIUserProvider{
    
    public function validateKey($apiUser, $key) : bool;

    /**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param EndUserProvider $fallback
     */
    public function attach(EndUserProvider $fallback);
    
}
