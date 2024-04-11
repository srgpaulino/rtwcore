<?php
namespace App\Provider\SFRedemption;

interface SFRedemptionProvider {
    public function getSFRedemption(string $orderId): array;
    public function createSFRedemption(array $data): string;

	/**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param SFContactProvider $fallback
     */
//    public function attach(SFContractProvider $fallback);
}
