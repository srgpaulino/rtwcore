<?php

namespace App\Provider\Shop;

use App\Domain\Shop;

interface ShopProvider {
	public function getbyId(int $id) : Shop;

	/**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param ShopProvider $fallback
     */
    public function attach(ShopProvider $fallback);
}
