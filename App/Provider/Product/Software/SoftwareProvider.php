<?php

namespace App\Provider\Product\Software;

use App\Domain\API\Product\Software;
use App\Domain\API\Product\RedeemRequest;

interface SoftwareProvider
{
    public function redeem(RedeemRequest $request): Software;

    /**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param SoftwareProvider $fallback
     */
    public function attach(SoftwareProvider $fallback);
}