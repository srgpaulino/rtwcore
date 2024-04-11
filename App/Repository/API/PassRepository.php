<?php

namespace App\Repository\API;

use App\Repository\Repository;

/*Requests*/
use App\Domain\API\Pass\CreateRequest;
use App\Domain\API\Pass\RedeemRequest;
use App\Domain\API\Pass\InvalidateRequest;
use App\Domain\API\Pass\ListRequest;

/*Responses*/
use App\Domain\API\Pass\CreateResponse;
use App\Domain\API\Pass\RedeemResponse;
use App\Domain\API\Pass\InvalidateResponse;
use App\Domain\API\Pass\ListResponse;

/*Providers*/
use App\Provider\Pass\PassProvider;
use App\Provider\Shop\ShopProvider;
use App\Provider\Id\IdProvider;

/*Exceptions*/
use App\Domain\API\Pass\Exception\ExpiredPass;
use App\Domain\API\Pass\Exception\IncorrectShop;
use App\Domain\API\Pass\Exception\ShopDoesNotExist;
use App\Domain\API\Pass\Exception\UsedPass;
use App\Domain\API\Pass\Exception\PassDoesNotExist;
use App\Domain\API\Pass\Exception\PassNotYetStarted;
use App\Domain\API\Pass\Exception\PassPermission;
use App\Exception\InvalidErrorException;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use ParagonIE\Halite\HiddenString;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;
use \ParagonIE\EasyRSA\KeyPair;
use TFC\Library_TfcApi as TFCAPI;

class PassRepository extends Repository
{
    public function create(CreateRequest $request)
    {
        if (!empty($request) && $this->createRequestIsValid($request)) {
            try {
                return $this->_providers['passProvider']->create($request);
            } catch (Exception $e) {
                throw $e;
            } catch (Error $e) {
                throw new InvalidErrorException($e->getMessage());
            }
        }
    }

    public function redeem(RedeemRequest $request)
    {
        if (!empty($request) && $this->redeemRequestIsValid($request)) {
            try {
                return $this->_providers['passProvider']->redeem($request);
            } catch (Exception $e) {
                throw $e;
            } catch (Error $e) {
                throw new InvalidErrorException($e->getMessage());
            }
        }
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
            $ret = $this->_providers['passProvider']->validShop($request);
            $ret = $this->_providers['passProvider']->validPermission($request);
            return $ret;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function redeemRequestIsValid(RedeemRequest $request) : bool
    {
        try {
            $ret = true;
            $ret = $this->_providers['passProvider']->validShop($request);
            $ret = $this->_providers['passProvider']->validPermission($request);
            $ret = $this->_providers['passProvider']->validPassFormat($request);
            return $ret;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
