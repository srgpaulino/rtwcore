<?php

namespace App\Provider\Product\Software;

use App\Domain\API\Product\Software;

/* Requests */
use App\Domain\API\Product\RedeemRequest;

use PDO;

/* Exceptions */
use App\Domain\API\Product\Exception\NotEnoughPoints;
use App\Domain\API\Product\Exception\ProductAlreadyRedeemed;
use App\Domain\API\Product\Exception\ProductDoesNotExist;
use App\Domain\API\Product\Exception\ProviderError;

use TFCLog\TFCLogger;
use bjsmasth\Salesforce\CRUD as SFClient;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\String_;

class RealPassProvider implements PassProvider
{
    private $tfcpdo;
    private $sfClient;
    private $fallback;

    public function __construct(PDO $tfcpdo, SFClient $sfClient)
    {
        $this->tfcpdo = $tfcpdo;
        $this->sfClient = $sfClient;
    }

    public function redeem(RedeemRequest $request) : array
    {

        try {

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }

    }

}
    