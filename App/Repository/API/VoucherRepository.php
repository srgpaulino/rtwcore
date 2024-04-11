<?php

namespace App\Repository\API;

use App\Domain\API\Voucher\CheckValidityRequest;
use App\Repository\Repository;

/*Requests*/
use App\Domain\API\Voucher\CreateRequest;
use App\Domain\API\Voucher\ShopCreateRequest;
use App\Domain\API\Voucher\RedeemRequest;
use App\Domain\API\Voucher\DFRedeemRequest;
use App\Domain\API\Voucher\InvalidateRequest;
use App\Domain\API\Voucher\ListRequest;
use App\Domain\API\Voucher\CodeRequest;
use App\Domain\API\Voucher\VoucherWalletRequest;
use App\Domain\API\Voucher\ListVoucherWalletRequest;

/*Responses*/
use App\Domain\API\Voucher\CreateResponse;
use App\Domain\API\Voucher\DFActivateRequest;
use App\Domain\API\Voucher\ShopCreateResponse;
use App\Domain\API\Voucher\RedeemResponse;
use App\Domain\API\Voucher\InvalidateResponse;
use App\Domain\API\Voucher\ListResponse;
use App\Domain\API\Voucher\CodeResponse;

/*Providers*/
use App\Provider\Voucher\VoucherProvider;
use App\Provider\EndUser\EndUserProvider;
use App\Provider\Shop\ShopProvider;
use App\Provider\Id\IdProvider;

/*Exceptions*/
use App\Domain\API\Voucher\Exception\ExpiredVoucher;
use App\Domain\API\Voucher\Exception\IncorrectShop;
use App\Domain\API\Voucher\Exception\ShopDoesNotExist;
use App\Domain\API\Voucher\Exception\UsedVoucher;
use App\Domain\API\Voucher\Exception\VoucherDoesNotExist;
use App\Domain\API\Voucher\Exception\VoucherNotYetStarted;
use App\Domain\API\Voucher\Exception\VoucherPermission;
use App\Domain\API\Voucher\ReactivateRequest;
use App\Exception\InvalidErrorException;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use ParagonIE\Halite\HiddenString;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;
use \ParagonIE\EasyRSA\KeyPair;
use TFC\Library_TfcApi as TFCAPI;

class VoucherRepository extends Repository
{
    public function create(CreateRequest $request)
    {
        if (!empty($request) && $this->createRequestIsValid($request)) {
            try {
                return $this->_providers['voucherProvider']->create($request);
            } catch (\Exception $e) {
                throw $e;
            } catch (\Error $e) {
                throw new InvalidErrorException($e->getMessage());
            }
        }
    }

    public function shopCreate(ShopCreateRequest $request)
    {
        if (!empty($request)) {
            try {
                return $this->_providers['voucherProvider']->shopCreate($request);
            } catch (\Exception $e) {
                throw $e;
            } catch (\Error $e) {
                throw new InvalidErrorException($e->getMessage());
            }
        }
    }

    public function redeem(RedeemRequest $request)
    {
        if (!empty($request) && $this->redeemRequestIsValid($request)) {
            try {
                $req = $request->getData();
                if(isset($req['redemption_value']) && $request->redemption_value>0) {
                    return $this->_providers['voucherProvider']->redeemPointsFromVoucher($request);        
                }
                return $this->_providers['voucherProvider']->redeem($request);
            } catch (\Exception $e) {
                throw $e;
            } catch (\Error $e) {
                throw new InvalidErrorException($e->getMessage());
            }
        }
    }

    public function dfRedeem(DFRedeemRequest $request)
    {
        if (!empty($request) && $this->dfRedeemRequestIsValid($request)) {
            try {
                return $this->_providers['voucherProvider']->dfRedeem($request);
            } catch (\Exception $e) {
                throw $e;
            } catch (\Error $e) {
                throw new InvalidErrorException($e->getMessage());
            }
        }
    }

    public function list(ListRequest $request)
    {
        if (!empty($request) && $this->listRequestIsValid($request)) {
            try {
                return $this->_providers['voucherProvider']->list($request);
            } catch (\Exception $e) {
                throw $e;
            } catch (\Error $e) {
                throw new InvalidErrorException($e->getMessage());
            }
        }
    }

    public function listVoucherWallet(ListVoucherWalletRequest $request)
    {
        try {
            return $this->_providers['voucherProvider']->listVoucherWallet($request);
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw new InvalidErrorException($e->getMessage());
        }
    }

    public function getVoucherWallet(VoucherWalletRequest $request)
    {
        try {
            return $this->_providers['voucherProvider']->getVoucherWallet($request);
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw new InvalidErrorException($e->getMessage());
        }
    }


    public function isValid(String $code)
    {
        if (!empty($code)) {
            try {
                return $this->_providers['voucherProvider']->isValid($code);
            } catch (\Exception $e) {
                throw $e;
            } catch (\Error $e) {
                throw new InvalidErrorException($e->getMessage());
            }
        }
    }

    public function reactivate(ReactivateRequest $request)
    {
        if (!empty($request) && $this->reactivateRequestIsValid($request)) {
            try {
                return $this->_providers['voucherProvider']->reactivate($request->code);
            } catch (\Exception $e) {
                throw $e;
            } catch (\Error $e) {
                throw new InvalidErrorException($e->getMessage());
            }
        }
    }

    public function invalidate(InvalidateRequest $request)
    {
        if (!empty($request) && $this->invalidateRequestIsValid($request)) {
            try {
                return $this->_providers['voucherProvider']->invalidate($request->code);
            } catch (\Exception $e) {
                throw $e;
            } catch (\Error $e) {
                throw new InvalidErrorException($e->getMessage());
            }
        }
    }

    public function dfActivate(DFActivateRequest $request)
    {
        if (!empty($request) && $this->activateRequestIsValid($request)) {
            try {
                return $this->_providers['voucherProvider']->dfActivate($request);
            } catch (\Exception $e) {
                throw $e;
            } catch (\Error $e) {
                throw new InvalidErrorException($e->getMessage());
            }
        }
    }

    public function vcode(CodeRequest $request)
    {
        if (!empty($request) && $this->visualFormatIsValid($request)) {
            try {
                return $this->_providers['voucherProvider']->vCode($request);
            } catch (\Exception $e) {
                throw $e;
            } catch (\Error $e) {
                throw new InvalidErrorException($e->getMessage());
            }
        }
    }

    public function imageCode($code, $format)
    {  
        return $this->_providers['voucherProvider']->imageCode($code, $format);
    }
   
    /** Private Functions **/

    private function filterIsValid(string $filter=null) : bool
    {
        return isJson($filter);
    }

    private function createRequestIsValid(CreateRequest $request) : bool
    {
        try {
            $ret = true;
            $ret = $this->_providers['voucherProvider']->validShop($request);
            $ret = $this->_providers['voucherProvider']->validPermission($request);
            return $ret;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function redeemRequestIsValid(RedeemRequest $request) : bool
    {
        try {
            $ret = true;
            $ret = $this->_providers['voucherProvider']->validShop($request);
            $ret = $this->_providers['voucherProvider']->validPermission($request);
            $ret = $this->_providers['voucherProvider']->validVoucherFormat($request);
            $ret = $this->_providers['voucherProvider']->voucherIsActive($request->code);
            return $ret;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function dfRedeemRequestIsValid(DFRedeemRequest $request) : bool
    {
        try {
            $ret = true;

            $ret = $this->_providers['voucherProvider']->validVoucherFormat($request);

            //validate voucher
            $ret = $this->_providers['voucherProvider']->voucherExists($request->voucherId);
            $ret = $this->_providers['voucherProvider']->voucherIsActive($request->voucherId);

            //validate validity
            $ret = $this->_providers['voucherProvider']->voucherNotExpired($request->voucherId);

            //validate voucher against location
            $ret = $this->_providers['voucherProvider']->validVoucherforLocation($request->voucherId, $request->extra['IATA']);

            //validate currency
            $ret = $this->_providers['voucherProvider']->validCurrencyForVoucher($request->voucherId, $request->extra['currency']);

            return $ret;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function listRequestIsValid(ListRequest $request) : bool
    {
        try {
            $ret = true;
            $ret = $this->_providers['voucherProvider']->validPermission($request);
            return $ret;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function checkValidityRequestIsValid(CheckValidityRequest $request) : bool
    {
        try {
            $ret = true;
            $ret = $this->_providers['voucherProvider']->voucherExists($request->code);
            return $ret;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function reactivateRequestIsValid(ReactivateRequest $request) : bool
    {
        try {
            $ret = true;            
            $ret = $this->_providers['voucherProvider']->voucherExists($request->code);

            //voucher has not expired


            //voucher has been invalidated


            return $ret;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function activateRequestIsValid(DFActivateRequest $request) : bool
    {
        try {
            $ret = true;   
            //voucher exists         
            $ret = $this->_providers['voucherProvider']->voucherExists($request->voucher);
            //enduser exists
            $ret = $this->_providers['endUserProvider']->endUserExists($request->endUser);
            return $ret;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function invalidateRequestIsValid(InvalidateRequest $request) : bool
    {
        try {
            $ret = true;
            $ret = $this->_providers['voucherProvider']->voucherExists($request->code);
            return $ret;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function visualFormatIsValid(CodeRequest $request) : bool
    {
        try{
            $ret = true;
            $ret = $this->_providers['voucherProvider']->visualFormatIsValid($request->format);
            return $ret;
        } catch (\Exception $e) {
            throw $e;
        }
    }

}
