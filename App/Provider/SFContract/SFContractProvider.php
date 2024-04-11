<?php
namespace App\Provider\SFContract;

interface SFContractProvider {
    public function getSFContract(string $search);
    public function updateSFContractStatus(Array $data): int;

	/**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param SFContactProvider $fallback
     */
//    public function attach(SFContractProvider $fallback);
}
