<?php

namespace App\Provider\Product\Redemption;

use App\Domain\API\Product\RedeemResponse;

/* Requests */
use App\Domain\API\Product\RedeemRequest;

use PDO;
use SoapClient;

/* Exceptions */
use App\Domain\API\EndUser\Exception\NotEnoughPoints;
use App\Domain\API\EndUser\Exception\ProductAlreadyRedeemed;
use App\Domain\API\EndUser\Exception\UserNotAllowedToRedeem;
use App\Domain\API\Product\Exception\ProductDoesNotExist;
use App\Domain\API\Product\Exception\ProviderError;
use App\Domain\API\Product\Exception\WrongProductPrice;
use App\Domain\API\EndUser\Exception\UserDoesNotExist;
use App\Domain\API\Shop\Exception\ShopDoesNotExist;
use App\Domain\API\Shop\Exception\DisabledShopException;
use App\Domain\API\Product\Exception\ProductNotAvailableInThisTerritory;
use App\Domain\API\Exception\CriticalAPIException;

use TFCLog\TFCLogger;
use bjsmasth\Salesforce\CRUD as SFClient;
use App\Repository\Logger\DBLogger;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\String_;

use App\Helper\Providers\Provider;

class RealRedemptionProvider implements RedemptionProvider
{

    private $tfcpdo;
    private $sfClient;
    private $fallback;
    private $logger;


    private $order;
    private $shopId;

    //User salesforce fields
    private $userSfFields = [
        'Id' => 'Id',
        'coins_count__c' => 'coins_count__c',
        'name__c' => 'name__c',
        'geo_id__c' => 'geo_id__c',
        'address_1__c' => 'address_1__c',
        'address_2__c' => 'address_2__c',
        'city__c' => 'city__c',
        'client_id__c' => 'client_id__c',
	    'client_lookup__c' => 'client_lookup__c',
        'date_of_birth__c' => 'date_of_birth__c',
        'email__c' => 'email__c',
        'last_name__c' => 'last_name__c',
        'phone__c' => 'phone__c',
        'phone_cc__c' => 'phone_cc__c',
        'zip__c' => 'zip__c',
        'state__c' => 'state__c',
        'language__c' => 'language__c',
        'mobile_phone__c' => 'mobile_phone__c',
        'mobile_phone_cc__c' => 'mobile_phone_cc__c',
        'handset_id__c' => 'handset_id__c',
        'handset_manufacturer__c' => 'handset_manufacturer__c',
        'handset_model__c' => 'handset_model__c',
        'login__c' => 'login__c',
        'password__c' => 'password__c',
        'theme__c' => 'theme__c',
        'reg_confirmed__c' => 'reg_confirmed__c',
        'reg_hash__c' => 'reg_hash__c',
        'additional_info_1__c' => 'additional_info_1__c',
        'additional_info_2__c' => 'additional_info_2__c',
        'tc_approved__c' => 'tc_approved__c',
        'external_user_id__c' => 'external_user_id__c',
        'external_user_level__c' => 'external_user_level__c',
        'account_disabled__c' => 'account_disabled__c',
        'zinio_email__c' => 'zinio_email__c',
        'zinio_user_id__c' => 'zinio_user_id__c',
        'disable_redemptions__c' => 'disable_redemptions__c'
    ];

    /**
     * Site content abbreviations. Used primarily for order IDs
     * Format: content type id => content abbreviation
     *
     * @var array
     */
    private $contentAbbr = array(
        'music' => 'msc',
        'games' => 'gms',
        'software' => 'sft',
        'audiobooks' => 'abk',
        'ebooks' => 'ebk',
        'movies' => 'mov',
        'emagazines' => 'emg',
        'giftcards' => 'gc',
        'newspapers' => 'np',
    );

    private $cdnDomain = "https://d1784eghclpht2.cloudfront.net";

    private $provider;

    public function __construct(PDO $tfcpdo, SFClient $sfClient, DBLogger $logger, Provider $provider)
    {
        $this->tfcpdo = $tfcpdo;
        $this->sfClient = $sfClient;
        $this->logger = $logger;
        $this->provider = $provider;
    }

    public function redeem(RedeemRequest $request, String $category) : array
    {

        try {

            $this->shopId = $request->shop_id;            

            //get shop
            $shop = $this->getShop($request->shop_id);

            $shop['mappedTerritory'] = $this->provider->getMappedTerritory($request->country, $category);

            //get user
            $user = $this->getUser($request->end_user, $request->shop_id);

            //get product
            $product = $this->getProduct($category, $request->product_id, $request->country, $shop);

            //check point values match
            /*if($product['price']['coins'] != $request->value) {
                throw new WrongProductPrice();
            }*/

            //generate order ID
            $orderId = $this->generateOrderId($category, $user['Id']);

            $this->logger->setShop($request->shop_id);
            $order = [
                "provider" => "Nexway",
                "content_name" => $category,
                "user_id" => $request->end_user,
                "user_name" => $user['login__c'],
                "product_id" => $product['provider_product_id'],
                "product_name" => $product['title'],
                "order_id" => $orderId
            ];
            $this->logger->startOrder($order);
            $this->order = $order;

            $this->mclog("STARTED", "Redemption started", $orderId);

            //prepare suplier request
            $redemption = $this->executeRedemption($request, $shop, $user, $product, $orderId, $category);

            //send request
            //$redemption = $provider->redeem();

            //return result
            return $redemption;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }

    }


    /** PRIVATE FUNCTIONS */
    private function getShop($shop) {
        try {

            $sql = 'SELECT 
                    shops.`id` as `id`,
                    shops.`name` as `name`, 
                    shops.`abbreviation` as `abbreviation`, 
                    shops.`enabled` as `enabled`
                FROM shops
                WHERE
                    shops.`id` = :shopId';
            
            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":shopId", $shop, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            //throw error if shop is not enabled
            if($res['enabled']===0) {
                throw new DisabledShopException();
            }

            if (isset($res['id'])) {
                $shop = $res;

                $sql = "SELECT 
                        setting_key, `value`
                    FROM shops_settings 
                    WHERE 
                        shop_id = :shopId
                    AND 
                        setting_key IN ('basecurrency', 'currencyexchangerate', 'displaycurrency')";
                $stmt = $this->tfcpdo->prepare($sql);
                $stmt->bindValue(":shopId", $res['id'], PDO::PARAM_STR);
                $stmt->execute();
    
                $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                foreach($res as $r) {
                    $aux = explode(':', $r['value']);
                    $shop[$r['setting_key']] = str_replace(";", "", str_replace("\"", "", $aux[2]));
                }
                
                return $shop;
                
            }

            throw new ShopDoesNotExist();


        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function getUser($endUserId, $shopId) {
        try {
            $slresult = $this->sfClient->query("SELECT " . implode(',', $this->userSfFields) . " FROM TFC_User__c WHERE Id = '" . $endUserId . "' AND client_id__c = ". $shopId);

            if ($slresult['totalSize'] === 1) {
                foreach ($slresult['records'] as $user) {

                    if(!empty($user['account_disabled__c'])) {
                        throw new DisabledUserException();
                    }

                    if(!empty($user['disable_redemptions__c'])) {
                        throw new UserNotAllowedToRedeem();
                    }

                    return $user;
                }
            }

            throw new UserDoesNotExist();

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function getProduct($category, $pid, $country, $shop) {

        switch($category){

            case 'games':
                return $this->getGame($pid, $country, $shop);
                break;
            case 'software':
                return $this->getSoftware($pid, $country, $shop);
                break;
            case 'emagazines':
                return $this->getEmagazines($pid, $country, $shop);
                break;

        }

    }

    private function getGame($pid, $country, $shop) {
        try {
        
            $sql = "SELECT 
                    games.id AS id,
                    games.title AS title,
                    games.image AS image,
                    games.provider_product_id AS provider_product_id,
                    games.provider_id AS provider_id,
                    games.publisher AS publisher,
                    games.price_eur AS price_eur,
                    games.price_gbp AS price_gbp,
                    games.price_usd AS price_usd,
                    games.price_cad AS price_cad,
                    games_territories.is_downloadable AS is_downloadable,
                    providers.name AS provider_name,
                    games.is_promo,
                    games.promo_start,
                    games.promo_end,
                    games.promo_discount
                FROM 
                    games_territories  
                INNER JOIN 
                    games ON(games_territories.product_id=games.id) 
                INNER JOIN 
                    providers ON(games.provider_id=providers.id) 
                WHERE 
                    games.id = :id";

            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":id", $pid, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            if (!isset($res['id'])) {
                throw new ProductDoesNotExist();
            }

            if(!$this->gameIsAvailableInTerritory($res['id'], $shop)) {
                throw new ProductNotAvailableInThisTerritory();
            }
        
            $product = $res;
            $product['price'] = $this->getGamePrice($product, $country, $shop);

            return $product;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function gameIsAvailableInTerritory($pid, $shop) {

        $sql = "SELECT 
                gt.product_id as product_id, gt.territory_id as territory_id, gt.is_downloadable as is_downloadable 
            FROM games_territories gt 
            JOIN territories t ON gt.territory_id = t.territory_id
            WHERE 
                gt.product_id=:pid AND code=:country";

        $stmt = $this->tfcpdo->prepare($sql);
        $stmt->bindValue(":pid", $pid, PDO::PARAM_STR);
        $stmt->bindValue(":country", $shop['mappedTerritory']['mapped_code'], PDO::PARAM_STR);
        $stmt->execute();

        $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        if(isset($res['product_id'])) {
            return true;
        }

        return false;

    }

    private function getGamePrice($product, $territory, $shop)
    {

        $sql = "SELECT tax, transactionFee, margin, discount FROM margins WHERE provider=:provider";
        $stmt = $this->tfcpdo->prepare($sql);
        $stmt->bindValue(":provider", 'Nexway', PDO::PARAM_STR);
        $stmt->execute();

        $margins = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $currencyExchangeRate = $this->getExchangeRate($shop['mappedTerritory']['currency'], $shop['basecurrency']);

        $price = $product["price_" . strtolower($shop['mappedTerritory']['currency'])];

        $currency = $shop['mappedTerritory']['currency'];

        $costPrice = $price * 0.7;
        if ((int)$price === 0 || (int)$costPrice === 0) {
                    $margin = 1;
                }
                else {
                    $margin = $price / $costPrice;
                }

                $margin = $margin;

                $transactionPrice = $costPrice * $currencyExchangeRate;

                $coins = ($transactionPrice) * $margin *
                         (float)$shop['currencyexchangerate'];

                $coins = round($coins);

                return array(
                    'feed_price'           => round(($costPrice) * $margin, 2),
                    'feed_currency'        => strtoupper($currency),
                    'cost_price'           => $costPrice,
                    'transaction_price'    => round(($transactionPrice) * $margin , 2),
                    'transaction_currency' => $shop['basecurrency'],
                    'currencyexchangerate' => (float)$shop['currencyexchangerate'],
                    'coins'                => $coins
                );

    }


    private function getSoftware($pid, $country, $shop) {
        try {
        
            $sql = "SELECT 
                    software.id AS id,
                    software.title AS title,
                    software.image AS image,
                    software.provider_product_id AS provider_product_id,
                    software.provider_id AS provider_id,
                    software.publisher AS publisher,
                    software.price_eur AS price_eur,
                    software.price_gbp AS price_gbp,
                    software.price_usd AS price_usd,
                    software.price_cad AS price_cad,
                    software_territories.is_downloadable AS is_downloadable,
                    providers.name AS provider_name,
                    software.is_promo,
                    software.promo_start,
                    software.promo_end,
                    software.promo_discount
                FROM 
                    software_territories  
                INNER JOIN 
                    software ON(software_territories.product_id=software.id) 
                INNER JOIN 
                    providers ON(software.provider_id=providers.id) 
                WHERE 
                    software.id = :id";

            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":id", $pid, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            if (!isset($res['id'])) {
                throw new ProductDoesNotExist();
            }

            if(!$this->softwareIsAvailableInTerritory($res['id'], $shop)) {
                throw new ProductNotAvailableInThisTerritory();
            }
        
            $product = $res;
            $product['price'] = $this->getSoftwarePrice($product, $country, $shop);
            return $product;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function softwareIsAvailableInTerritory($pid, $shop) {

        $sql = "SELECT 
                st.product_id as product_id, st.territory_id as territory_id, st.is_downloadable as is_downloadable 
            FROM software_territories st 
            JOIN territories t ON st.territory_id = t.territory_id
            WHERE 
                st.product_id=:pid AND code=:country";

        $stmt = $this->tfcpdo->prepare($sql);
        $stmt->bindValue(":pid", $pid, PDO::PARAM_STR);
        $stmt->bindValue(":country", $shop['mappedTerritory']['mapped_code'], PDO::PARAM_STR);
        $stmt->execute();

        $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        if(isset($res['product_id'])) {
            return true;
        }

        return false;

    }

    private function getSoftwarePrice($product, $territory, $shop)
    {

        $sql = "SELECT tax, transactionFee, margin, discount FROM margins WHERE provider=:provider";
        $stmt = $this->tfcpdo->prepare($sql);
        $stmt->bindValue(":provider", 'Nexway', PDO::PARAM_STR);
        $stmt->execute();

        $margins = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $currencyExchangeRate = $this->getExchangeRate($shop['mappedTerritory']['currency'], $shop['basecurrency']);

        $price = $product["price_" . strtolower($shop['mappedTerritory']['currency'])];

        $currency = $shop['mappedTerritory']['currency'];

        $costPrice = $price * 0.7;
        if ((int)$price === 0 || (int)$costPrice === 0) {
                    $margin = 1;
                }
                else {
                    $margin = $price / $costPrice;
                }

                $transactionPrice = $costPrice * $currencyExchangeRate; 

                $margin = $margin;

                $coins = ($transactionPrice) * $margin *
                         (float)$shop['currencyexchangerate'];

                $coins = round($coins);

                return array(
                    'feed_price'           => round(($costPrice) * $margin, 2),
                    'feed_currency'        => strtoupper($currency),
                    'cost_price'           => $costPrice,
                    'transaction_price'    => round(($transactionPrice) * $margin, 2),
                    'transaction_currency' => $shop['basecurrency'],
                    'currencyexchangerate' => (float)$shop['currencyexchangerate'],
                    'coins'                => $coins
                );

    }

    private function getEmagazine($pid, $country, $shop) {
        try {
        
            $sql = "SELECT 
                    emagazines.id AS id,
                    emagazines.url_suffix AS url_suffix,
                    emagazines.title AS title,
                    emagazines.frequency AS frequency,
                    emagazines.publisher AS publisher,
                    emagazines.current_issue_id AS current_issue_id,
                    emagazines.long_description AS long_description,
                    emagazines.image AS image,
                    emagazines.provider_magazine_id AS provider_product_id,
                    emagazines.provider_id AS provider_id,
                    emagazines.publish_country AS publish_country,
                    emagazines.issue_title AS issue_title,
                    emagazines.lang_id AS lang_id,
                    emagazines.url_suffix,
                    emagazines.price_eur AS price_eur,
                    emagazines.price_gbp AS price_gbp,
                    emagazines.price_usd AS price_usd,
                    emagazines.price_cad AS price_cad,
                    emagazines.enabled,
                    emagazines.available_date AS available_date,
                    providers.name AS provider_name
                FROM 
                    emagazines_territories  
                INNER JOIN 
                    emagazines ON(emagazines_territories.emagazine_id=emagazines.id)
                INNER JOIN 
                    providers ON(emagazines.provider_id=providers.id) 
                WHERE
                    emagazines.available_date <= :available_date
                    emagazines.id = :id";

            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":available_date", date("Y-m-d H:i:s"), PDO::PARAM_STR);
            $stmt->bindValue(":id", $pid, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            if (!isset($res['id'])) {
                throw new ProductDoesNotExist();
            }

            if(!$this->emagazineIsAvailableInTerritory($res['id'], $shop)) {
                throw new ProductNotAvailableInThisTerritory();
            }
        
            $product = $res;
            $product['price'] = $this->getEmagazinePrice($product, $country, $shop);
            return $product;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function emagazineIsAvailableInTerritory($pid, $shop) {

        $sql = "SELECT 
                et.product_id as product_id, et.territory_id as territory_id, et.is_downloadable as is_downloadable 
            FROM emagazine_territories et 
            JOIN territories t ON et.territory_id = e.territory_id
            WHERE 
                et.product_id=:pid AND code=:country";

        $stmt = $this->tfcpdo->prepare($sql);
        $stmt->bindValue(":pid", $pid, PDO::PARAM_STR);
        $stmt->bindValue(":country", $shop['mappedTerritory']['mapped_code'], PDO::PARAM_STR);
        $stmt->execute();

        $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        if(isset($res['product_id'])) {
            return true;
        }

        return false;

    }

    private function getEmagazinePrice($product, $territory, $shop)
    {

        $sql = "SELECT tax, transactionFee, margin, discount FROM margins WHERE provider=:provider";
        $stmt = $this->tfcpdo->prepare($sql);
        $stmt->bindValue(":provider", 'Zinio', PDO::PARAM_STR);
        $stmt->execute();

        $margins = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $currencyExchangeRate = $this->getExchangeRate($shop['mappedTerritory']['currency'], $shop['basecurrency']);

        $price = $product["price_" . strtolower($shop['mappedTerritory']['currency'])];

        $currency = $shop['mappedTerritory']['currency'];

        $costPrice = $price * (1-$margins['discount']);
        if ((int)$price === 0 || (int)$costPrice === 0) {
                    $margin = 1;
                }
                else {
                    $margin = $price / $costPrice;
                }

                $transactionPrice = $costPrice * $currencyExchangeRate; 

                $margin = $margin;

                $coins = ($transactionPrice) * $margin *
                         (float)$shop['currencyexchangerate'];

                $coins = round($coins);

                return array(
                    'feed_price'           => round(($costPrice) * $margin, 2),
                    'feed_currency'        => strtoupper($currency),
                    'cost_price'           => $costPrice,
                    'transaction_price'    => round(($transactionPrice) * $margin, 2),
                    'transaction_currency' => $shop['basecurrency'],
                    'currencyexchangerate' => (float)$shop['currencyexchangerate'],
                    'coins'                => $coins
                );

    }

    private function getExchangeRate($from, $to) {

        $sql = "SELECT `" . $to . "` as `to` FROM `exchange_rates` WHERE currency = :from";
        $stmt = $this->tfcpdo->prepare($sql);
        $stmt->bindValue(":from", $from, PDO::PARAM_STR);
        $stmt->execute();

        $exchange = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return $exchange['to'];

    }

    private function generateOrderId($category, $userId)
    {
        $abbr = $this->contentAbbr[$category];

        $orderId = strtoupper($abbr) . $userId . time();
        return $orderId;
    }

    private function isFirstRedemption($endUser)
    {
        $slresult = $this->sfclient->query("SELECT Id FROM Download_History__c WHERE TFC_User__c = '" . $endUser . "' AND Category_ID__c = 'games'");

        if ($slresult['totalSize'] > 0) {
            return false;
        }

        return true;
    }

    private function executeRedemption($request, $shop, $user, $product, $orderId, $category) : Array
    {

        switch($category){

            case 'games':
            case 'software':
                return $this->redeemNexway($request, $shop, $user, $product, $orderId, $category);
                break;
            case 'emagazines':
                return $this->redeemZinio($request, $shop, $user, $product, $orderId);
                break;

        }

    }

    private function redeemNexway($request, $shop, $user, $product, $orderId, $category) : Array
    {

        $data = [
            'request' => $request->getData(),
            'orderId' => $orderId,
            'contentType' => $category,
            'product' => $product,
            'user' => $user,
            'shop' => $shop
        ];

        $this->provider->setLogger($this->logger->getLogId(), $this->order, $this->shopId);

        $response = $this->provider->download($category, $data);

        $this->mclog("START", "Nexway Response: " . json_encode($response), $orderId);

        if (isset($response['logs'])) {
            if (isset($response['logs']['error'])) {
                $this->mclog("ERROR", $message, $orderId, "PROVIDER");
                throw new ProviderError($message);
                $this->disableItem($data['provider_product_id']);
            }
            return false;
        }

        $downloadManager = $response['downloadManager'];

        if(!isset($response['orderLines'])) {
            $this->mclog("ERROR", "Product not available in this territory", $orderId, "USER");
            throw new ProductNotAvailableInThisTerritory();
        }

        $orderLine  = $response['orderLines'][0];
        $result = [];
        if(!empty($orderLine)) {
            $result = $orderLine;
        }

        

        // Serial numbers
        $data['remark'] = '';
        $data['additional_info'] = '';
        $i = 1;

        foreach($orderLine['lineItems'] as $lineItem) {

            if (isset($lineItem['serials'])) {
                foreach($lineItem['serials'] as $serial) {
                    $data['additional_info'] .= 'Serial number ' . $i . ': ' . $serial['data'] . "\n";
                    $data['serial'] = $serial['data'];
                    $i++;
                }
            }

            
        }
        $data['additional_info'] = trim($data['additional_info']);
        if(isset($orderLine['remark'])) {
            $data['remark'] = $orderLine['remark'];
            $data['additional_info'] = $data['remark'] . "<br /><br />" . $data['additional_info'];
        }
        $data['download_end_date'] = $orderLine['dateEndDownload'];

        //download url
        $download_url = [];
        if(isset($result['files'])) {

            foreach($result['files'] as $file) {
                if(strpos($file['url'], '.dmg')) {
                    $download_url['mac'] = $file['url'];
                } else {
                    $download_url['pc'] = $file['url'];
                }
                
            }
        }

        // Download url or steam download
        if(!empty($downloadManager)) {

            // Check for MAC
            $mac = false;
            if(!empty($data['product']['os'])) {
                foreach($data['product']['os'] as $os) {
                    if(strpos($os['name'],'mac')) {
                        $mac = true;
                        break;
                    }
                }
            }

            // Download url
            if($mac && !empty($downloadManager['mac'])) {
                $data['download_url']['mac'] = $download_url['mac'];
            } elseif(!$mac && !empty($downloadManager['pc'])) {
                $data['download_url'] = $downloadManager['pc'];
            } else {
                throw new ProviderError('Cannot find download manager file, download manager content: ' . print_r($result['downloadManager'], true));
                
                // If item can't be downloaded, we disable it to avoid further errors
                $this->disableItem($data['product']['provider_product_id']);

                return false;
            }
        } else {
            if(isset($download_url)){
                $data['download_url'] = $download_url;
            } else {
                $data['download_url'] = false;
            }
                
        }

        

        // Add order to download history
        $data['content_type'] = $category;

        $this->mclog("STARTED", "Saving into Salesforce", $orderId);
        $this->addDownloadHistory($data);
        $this->mclog("OK", "Redemption completed successfully.", $orderId);

        return [
            'product_id' => $data['product']['id'],
            'end_user'  => $data['user']['Id'],
            'order_id'  => $data['orderId'],
            'value'     => $data['product']['price']['coins'],
            'download'  => [
                'instructions' => $data['remark'],
                'url'          => $data['download_url']
            ]
        ];

    }

    private function redeemZinio($request, $shop, $user, $product) {

        $data = [
            'request' => $request,
            'orderId' => $orderId,
            'contentType' => $category,
            'product' => $product,
            'user' => $user,
            'shop' => $shop
        ];

    }

    private function addDownloadHistory($data)
    {
        // Fields check
        $fieldsCheck = array(
            'isrc'              	    => '',
            'artist'            	    => '',
            'publisher'         	    => '',
            'sf_content_type'   	    => '',
            'download_end_date' 	    => '1970-01-01',
            'additional_info'   	    => '',
            'tmScore'           	    => '',
            'tmVerified'        	    => 0,
            'download_url'      	    => '',
            'external_url'              => '',
            'voucher_code'      	    => '',
            'gifted'            	    => 0,
            'serial'            	    => '',
	        'first_redemption'		    => 0,
            'newsletter_subscribed' 	=> 0,
            'sd_voucher'                => 0,
            'hd_voucher'                => 0,
            'premium_voucher'           => 0,
            'is_promo'                  => 0,
            'content_voucher_used'      => 0
        );
        foreach($fieldsCheck as $field => $value) {
            if(!isset($data[$field]) || empty($data[$field])) {
                $data[$field] = $value;
            }
        }

        $transactionEquivalent = $data['product']['price']['transaction_price'];
        if(!empty($data['product']['price']['currencyexchangerate'])) {
            $transactionEquivalent = $data['product']['price']['coins'] / $data['product']['price']['currencyexchangerate'];
        }
        $transactionEquivalent = $data['product']['price']['transaction_equivalent'] = round($transactionEquivalent, 4);

        $platformInfo = 'API';

        $userEmail = $data['user']['email__c'];

        // We don't want to store the user email for SGI shops
        // if (($this->auth->getLogged('client_id__c') == 41) || ($this->auth->getLogged('client_id__c') == 43) || ($this->auth->getLogged('client_id__c') == 50) || ($this->auth->getLogged('client_id__c') == 52) || ($this->auth->getLogged('client_id__c') == 54)) {
        //     $userEmail = '';
        // }

        $srpPriceBeforeDiscount = $data['product']['price']['feed_price'];
        if(empty($srpPriceBeforeDiscount)) {
            $srpPriceBeforeDiscount = 0.0;
        };

        
        $fieldset = array(
            // Info
            'Category_ID__c'            => substr($data['content_type'], 0, 50),
            'content_id__c'             => $data['product']['provider_product_id'],
            'content_title__c'          => substr($data['product']['title'], 0, 250),
            'Content_type__c'           => substr('', 0, 50),
            'product_image__c'          => $this->getImage($data, $data['content_type'], "model"),
            //'isrc__c'                   => substr('', 0, 50),
            //'artist__c'                 => substr('', 0, 100),
            'order_id__c'               => substr($data['orderId'], 0, 200),
            'TFC_User__c'               => $data['user']['Id'],
            'Email__c'                  => $userEmail,
            'Feed_provider__c'          => substr('Nexway', 0, 50),
            'Label_publisher_name__c'   => substr($data['product']['publisher'], 0, 100),
            'Shop_abbr__c'              => substr(strtoupper($data['shop']['abbreviation']), 0, 4),
            'username__c'               => substr($data['user']['login__c'], 0, 50),
            'User_territory__c'         => substr($data['user']['geo_id__c'], 0, 5),
            'download_end_date__c'      => $data['download_end_date'],
            'Additional_info__c'        => substr($data['additional_info'], 0, 32768),
            'download_url__c'           => substr(json_encode($data['download_url']), 0, 1500),
            //'content_voucher__c'        => substr('', 0, 1500),
            //'voucher_code__c'           => substr('', 0, 250),
            // Prices
            'Cost__c'                       => $data['product']['price']['feed_price'],
            'currency__c'                   => $data['product']['price']['feed_currency'],
            'purchasing_price__c'           => $data['product']['price']['cost_price'],
            'transaction_equivalent__c'     => $transactionEquivalent,
            'client_currency__c'            => $data['product']['price']['transaction_currency'],
            'Clubcoins__c'                  => $data['product']['price']['coins'],
            'Platform__c'                   => $platformInfo,
            //'ThreatMetrix_Score__c'         => 0,
            //'ThreatMetrix_Verified__c'      => 0,
            'serial__c'                     => $data['serial'],
            //'newsletter_subscribed__c'      => 0,
            //'available_clubcoins__c'        => 0,
            //'first_redemption__c'           => 0,
            'Special_Offer__c'              => (intval($data['product']['is_promo'])==1?"true":"false"),
            'Discount__c'                   => floatval($data['product']['promo_discount']),
            'SRP_Price_Before_Discount__c'  => $srpPriceBeforeDiscount,
            //'Category_Voucher_Used__c'      => 0
            'Client_Order_ID__c'  => $data['request']['client_order_id']
            
        );

        $this->mclog("STARTED", 'Saving into Salesforce: ' . json_encode($fieldset), $data['orderId'], "");

        
        foreach($fieldset as $k => $v) {
            $fieldset[$k] = htmlspecialchars($v);
        }

        try {
            $result = $this->sfClient->create('Download_history__c', $fieldset);

            if(isset($result->id)){
                $data['sf_resultId'] = $result;
            }

        } catch(Exception $e) {
            $this->mclog("ERROR", 'Salesforce error: ' . $e, $data['orderId'], "SALESFORCE");

            throw new CriticalAPIException('Salesforce error: ' . $e);
        }
        
    }

    private function getImage($data, $contentType, $model) {

        if(!empty($data['product']['image'])) {
           if (isset($data['product']['current_issue_id'])) {
                $dataId = $data['product']['provider_product_id']."_".$data['product']['current_issue_id'];
            } else {
                $dataId = $data['product']['provider_product_id'];
            }

            $image = 'pi-medium-' . $data['product']['provider_id'] . '/' . $data['product']['image'] . '/' . $dataId;
        } else {
            $image = 'pi-blank';
        }

        $segments = explode('-', $image);

        return $this->cdnDomain . "/{$segments[2]}_{$segments[1]}.jpg";
    }

    private function mclog($status, $message, $orderId=NULL, $errorType=NULL) {
        $this->logger->status($status);
        $this->logger->message($message);        
        $this->logger->logIt();
    }




    /**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param RedemptionProvider $fallback
     */
    public function attach(RedemptionProvider $fallback)
    {
        $this->fallback = $fallback;
    }


}