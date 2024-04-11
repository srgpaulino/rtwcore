<?php

namespace App\Provider\EndUser;

use App\Domain\Base\EndUser;

interface EndUserProvider {
	public function getAll(string $filter = null, string $apiUser) : Array;

	/**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param EndUserProvider $fallback
     */
    public function attach(EndUserProvider $fallback);
}
