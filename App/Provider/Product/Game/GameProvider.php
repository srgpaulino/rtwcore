<?php

namespace App\Provider\Product\Game;

use App\Domain\API\Product\Game;
use App\Domain\API\Product\RedeemRequest;

interface GameProvider
{
    public function redeem(RedeemRequest $request): Game;

    /**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param GameProvider $fallback
     */
    public function attach(GameProvider $fallback);
}