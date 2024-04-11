<?php
namespace App\Provider\SFAccount;

use App\Domain\SalesforceAccount;

interface SFAccountProvider {
    
    public function getSFAccount(string $search, string $mode='single') : Array;
    public function createSFAccount(Array $data) : string;
    public function updateSFAccount(Array $data) : SalesforceAccount;
    public function sfContactsPerAccount(Array $data) : int;
    public function anonimizeSFAccount(Array $data) : SalesforceAccount;

	/**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param SFAccountProvider $fallback
     */
    public function attach(SFAccountProvider $fallback);
}
