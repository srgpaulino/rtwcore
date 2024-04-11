<?php

namespace App\Provider\Shop;

use App\Domain\Shop;
use \PDO;
use App\Exception\InvalidObjectException;
use App\Exception\InvalidLogException;
use bjsmasth\Salesforce\CRUD;
use App\Exception\DoesNotExistException;

class PDOShopProvider implements ShopProvider
{
    private $adminpdo;
    private $logpdo;
    private $tfcpdo;
    private $sfclient;
    private $fallback;

    //data structure for Shop object
    private $objKeys = [
        'id' => 'id',
        'name' => 'name',
        'url' => 'url',
        'email' => 'email',
        'abbreviation' => 'abbreviation',
        'type_id' => 'type_id',
        'enabled' => 'enabled',
        'is_default' => 'is_default',
        'is_connected' => 'is_connected',
        'account_id' => 'account_id'
    ];

    //data structure for Shop Collection
    private $colKeys = [
        'id' => 'id',
        'name' => 'name',
        'abbreviation' => 'abbreviation',
        'enabled' => 'enabled'
    ];

    //salesforce fields
    private $sfFields = [
        'clientId' => 'Name',
        'shop_name' => 'shop_name__c',
        'shop_abbr' => 'shop_abbr__c',
        'currency' => 'CurrencyIsoCode',
        'enabled' => 'enabled__c'

    ];

    private $mode = [
        'single'        => 'objKeys',
        'collection'    => 'colKeys'
    ];

    /**
     * 
     */
    public function __construct(PDO $adminpdo, PDO $logpdo, PDO $tfcpdo, CRUD $sfclient)
    {
        $this->adminpdo = $adminpdo;
        $this->logpdo = $logpdo;
        $this->tfcpdo = $tfcpdo;
        $this->sfclient = $sfclient;
    }

    /**
     * 
     */
    public function getById(int $id) : Shop 
    {

        try{
            $result = null;

            $sql = "CALL getShopById(:id)";
            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":id", $id, PDO::PARAM_STR);
            $stmt->execute();
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if(count($res)!==1)
            {
                throw new DoesNotExistException('Shop does not exist.');
            }

            $sfresult = $this->sfclient->query("SELECT Id, Name, AccountID__c FROM Account WHERE Client_shop__c = '$id' LIMIT 1");
            foreach ($sfresult['records'] as $account) {
                $res['account_id'] = $account['AccountID__c'];
            }

            return new Shop($res);

        }
        catch(\Exception $e) {
            throw $e;
        }
        catch(\Error $e) {
            throw $e;
        }
        
    }


    public function getShopCurrency($shop)
    {

        try {

            $sql = "CALL GetCurrency(:shop)";
            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":shop", $shop, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res)!==1) {
                throw new InvalidObjectException("Shop does not exist.");
            }

            foreach ($res as $conv) {
                $shopCurrency = $conv['shop_currency'];
            }

            ddd("shopCUrrency: ". $shopCurrency);

            return $shopCurrency;
        }
        catch(\Exception $e) {
            throw $e;
        }
        catch(\Error $e) {
            throw $e;
        }
    }


    public function getShopConversion(string $currency, string $shopCurrency)
    {
        try {

            $sql = "CALL ConvertBetweenCurrencies(:value, :currency, :shopCurrency)";
            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":value", 1, PDO::PARAM_STR);
            $stmt->bindValue(":currency", $currency, PDO::PARAM_STR);
            $stmt->bindValue(":shopCurrency", $shopCurrency, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new InvalidObjectException("Currency not available.");
            }

            foreach ($res as $conv) {
                $conversion = $conv[$conv['choose']];
            }

            return $conversion;
        }
        catch(\Exception $e) {
            throw $e;
        }
        catch(\Error $e) {
            throw $e;
        }
    }

    public function getShopRate(int $shop)
    {

        try{
            $shopCurrency = $this->getShopCurrency($shop);
            $sql = "CALL ConvertCurrency(1, :shopCurrency, :shopId)";
            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":shopCurrency", $shopCurrency, PDO::PARAM_STR);
            $stmt->bindValue(":shopId", $shop, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new InvalidObjectException("Currency not available.");
            }

            foreach ($res as $conv) {
                $conversion = $conv[$conv['choose']];
            }

            return $conversion;
        }
        catch(\Exception $e) {
            throw $e;
        }
        catch(\Error $e) {
            throw $e;
        }

    }


     /**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param ShopProvider $fallback
     */
    public function attach(ShopProvider $fallback)
    {
        $this->fallback = $fallback;
    }

}