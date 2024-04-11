<?php

namespace App\Repository\API;

use App\Repository\Repository;

/*Requests */
use App\Domain\API\Product\RedeemRequest;

/*Responses */
use App\Domain\API\Product\RedeemResponse;

/*Providers */
use App\Provider\Product\Redemption\RedemptionProvider;

/*Exceptions */
use App\Domain\API\Product\Exception\NotEnoughPoints;
use App\Domain\API\Product\Exception\ProductAlreadyRedeemed;
use App\Domain\API\Product\Exception\ProductDoesNotExist;
use App\Domain\API\Product\Exception\ProviderError;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use ParagonIE\Halite\HiddenString;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;
use \ParagonIE\EasyRSA\KeyPair;
use TFC\Library_TfcApi as TFCAPI;

class ProductRepository extends Repository
{

    public function gameRedeem(RedeemRequest $request)
    {
        if (!empty($request)) {
            try {
                return $this->_providers['nexwayRedemptionProvider']->redeem($request, "games");
            } catch (Exception $e) {
                throw $e;
            } catch (Error $e) {
                throw new InvalidErrorException($e->getMessage());
            }
        }
    }

    public function softwareRedeem(RedeemRequest $request)
    {
        if (!empty($request)) {
            try {
                return $this->_providers['nexwayRedemptionProvider']->redeem($request, "software");
            } catch (Exception $e) {
                throw $e;
            } catch (Error $e) {
                throw new InvalidErrorException($e->getMessage());
            }
        }
    }


}