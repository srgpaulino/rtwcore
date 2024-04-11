<?php

namespace App\Provider\Voucher;

use App\Domain\API\Voucher\CheckValidityRequest;
use App\Domain\API\Voucher\Voucher;
use App\Domain\API\Voucher\RedeemedVoucher;
use App\Domain\API\Voucher\CreateRequest;
use App\Domain\API\Voucher\InvalidatedVoucherResponse;
use App\Domain\API\Voucher\ShopCreateRequest;
use App\Domain\API\Voucher\RedeemRequest;
use App\Domain\API\Voucher\InvalidateRequest;
use App\Domain\API\Voucher\ListRequest;
use App\Domain\API\Voucher\ReactivatedVoucherResponse;
use App\Domain\API\Voucher\ReactivateRequest;

interface VoucherProvider {

    public function create(CreateRequest $request) : Array;
    public function shopCreate(ShopCreateRequest $request) : Array;
    public function list(ListRequest $request) : Array;
    public function redeem(RedeemRequest $request) : RedeemedVoucher;
    public function invalidate(String $code) : Array;    
    public function isValid(String $code) : Array;
    public function reactivate(String $code) : Array;

	/**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param VoucherProvider $fallback
     */
    public function attach(VoucherProvider $fallback);
}
