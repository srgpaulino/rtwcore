<?php

namespace App\Provider\Product\Redemption;

use App\Domain\API\Product\RedeemResponse;
use App\Domain\API\Product\RedeemRequest;

interface RedemptionProvider
{
    //public function redeem(RedeemRequest $request, String $category): RedeemResponse;

    /**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param GameProvider $fallback
     */
    public function attach(RedemptionProvider $fallback);
}