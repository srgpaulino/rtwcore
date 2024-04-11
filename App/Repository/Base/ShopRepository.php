<?php

namespace App\Base\Repository;

use App\Provider\Shop\ShopProvider;
use App\Provider\Id\IdProvider;
use App\Domain\Shop;
use DateTimeImmutable;
use App\Exception\DoesNotExistException;
use App\Exception\InvalidAttributeException;
use App\Exception\InvalidArgumentException;
use App\Exception\InvalidAuthenticationException;
use App\Exception\InvalidObjectException;
use App\Exception\InvalidErrorException;
use Ramsey\Uuid\Uuid;
use TFC\Library_TfcApi as TFCAPI;

class ShopRepository
{
    private $shopProvider;
    private $idProvider;

    private $requestStructure = [
        'id' => 'id'
    ];

    public function __construct(ShopProvider $shopProvider, IdProvider $idProvider)
    {
        $this->shopProvider = $shopProvider;
        $this->idProvider = $idProvider;
    }

    public function getById(int $id, string $apiUser)
    {
        if($this->isAuthorized($apiUser)){
            return $this->shopProvider->getById($id, $apiUser, $apiKey);
        }
        throw new InvalidAuthenticationException();
    }

}