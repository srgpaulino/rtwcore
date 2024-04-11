<?php

namespace App\Provider\Pass;

use App\Domain\API\Pass\Pass;
use App\Domain\API\Pass\RedeemedPass;
use App\Domain\API\Pass\CreateRequest;
use App\Domain\API\Pass\RedeemRequest;
use App\Domain\API\Pass\InvalidateRequest;
use App\Domain\API\Pass\ListRequest;

interface PassProvider
{
    public function create(CreateRequest $request) : array;
    public function list(ListRequest $request) : array;
    public function redeem(RedeemRequest $request) : RedeemedPass;
    public function invalidate(InvalidateRequest $request) : bool;

    /**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param PassProvider $fallback
     */
    public function attach(PassProvider $fallback);
}
