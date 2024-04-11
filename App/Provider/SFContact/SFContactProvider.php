<?php
namespace App\Provider\SFContact;

interface SFContactProvider {
    public function getSFContact(string $search, string $mode='single') : Array;
    public function createSFContact(Array $data) : string;
    public function updateSFContact(Array $data): Array;
    public function anonimizeSFContact(Array $data): Array;
    public function exists(string $email): bool;

	/**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param SFContactProvider $fallback
     */
    public function attach(SFContactProvider $fallback);
}
