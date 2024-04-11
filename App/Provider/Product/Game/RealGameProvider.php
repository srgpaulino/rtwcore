<?php

namespace App\Provider\Product\Game;

use App\Domain\API\Product\Game;

/* Requests */
use App\Domain\API\Product\RedeemRequest;

use PDO;

/* Exceptions */
use App\Domain\API\EndUser\Exception\NotEnoughPoints;
use App\Domain\API\EndUser\Exception\ProductAlreadyRedeemed;
use App\Domain\API\EndUser\Exception\UserNotAllowedToRedeem;
use App\Domain\API\Product\Exception\ProductDoesNotExist;
use App\Domain\API\Product\Exception\ProviderError;
use App\Domain\API\Product\Exception\WrongProductPrice;
use App\Domain\API\EndUser\Exception\UserDoesNotExist;

use TFCLog\TFCLogger;
use bjsmasth\Salesforce\CRUD as SFClient;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\String_;

class RealGameProvider implements GameProvider
{

    private $tfcpdo;
    private $sfClient;
    private $fallback;
    private $logger;

    /**
     * Provider contend can by redownloaded
     *
     * @var array
     */
    protected $_redownload = array('games' => true, 'software' => true);

    /**
     * Time limit during which content can be redownloaded
     *
     * @var array
     */
    protected $redownloadTime = array(
        'games'=>'21 days',
        'software'=>'21 days'
    );

    /**
     * Auth data
     *
     * @var array
     */
    protected $_auth = array(
        'GB' => '946c1ed05cf4ddcb',
        'DE' => '7d2188a34c1b226',
        'ES' => '868e22de8599524',
        'FR' => 'e27841d663eafa8',
        'IT' => '4e517770b36e954',
        'EU' => 'f82a56c2bddf804',
        'US' => 'bBhmBUNzh0LV2Ym'
    );

    /**
     * API url
     *
     * @var string
     */
    protected $_apiUrl = 'http://ws.prep.websizing.com/global/order/v2.2/soap?wsdl'; // Test env
    //protected $_apiUrl = 'https://webservices.nexway.com/global/order/v2.2/soap?wsdl'; // Prod env

    /**
     * Provider currency by territory
     *
     * @var array
     */
    public $currencyByTerritory = array(
        'GB'  => 'gbp',
        'DE'  => 'eur',
        'US'  => 'usd',
        'IT'  => 'eur',
        'FR'  => 'eur',
        'CA'  => 'cad',
        'ES'  => 'eur',
        'EU'  => 'eur',
        'ALL' => 'eur'
    );

    /**
     * Provider language by territory
     *
     * @var array
     */
    public $languageByTerritory = array(
        'GB'  => 'en_GB',
        'DE'  => 'de_DE',
        'US'  => 'en_US',
        'IT'  => 'it_IT',
        'FR'  => 'fr_FR',
        'CA'  => 'en_XE',
        'ES'  => 'es_ES',
        'EU'  => 'en_XE',
        'ALL' => 'en_XE'
    );

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
    public $contentAbbr = array(
        1 => 'msc',
        2 => 'gms',
        3 => 'sft',
        4 => 'mob',
        5 => 'abk',
        6 => 'ebk',
        7 => 'mov',
        8 => 'emg',
        10 => 'gc',
        11 => 'np',
    );

    public function __construct(PDO $tfcpdo, SFClient $sfClient)
    {
        $this->tfcpdo = $tfcpdo;
        $this->sfClient = $sfClient;
    }

    public function redeem(RedeemRequest $request) : Game
    {

        try {

            //get user info
            $user = $this->getUserInfo($request->end_user, $request->shop_id);

            //get shop info
            $shop = $this->getShopInfo($request->shop_id);
            
            //check if user is allowed to redeem
            if(!$this->canRedeem($user)) {
                throw new UserNotAllowedToRedeem();
            }

            //check if product exists
            $product = $this->getProduct($request->product_id);

            //generate order ID
            $orderId = $this->generateOrderId($user['Id']);

            //check if this is first redemption
            $firstRedemption = $this->isFirstRedemption();

            //check price
            if ($product->price < 0) {
                throw new WrongProductPrice();
            }

            //check if user has enough points
            if($product->price > $user->coins_count) {
                throw new NotEnoughPoints();
            }

            //process download
            $download  = $this->download($orderId, $product, $user, $shop);

            //update coins
            $coins = $user->coins_count - $product->price;

            //return redemption response
            return $download;
            
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }

    }

    /**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param GameProvider $fallback
     */
    public function attach(GameProvider $fallback)
    {
        $this->fallback = $fallback;
    }

    /* private functions */

    private function getUserInfo($endUserId, $shopId)
    {
        try {
            $slresult = $this->sfclient->query("SELECT " . implode(',', $this->sfFields) . " FROM TFC_User__c WHERE Id = '" . $endUserId . "' AND client_id__c = ". $shopId);

            if ($slresult['totalSize'] === 1) {
                foreach ($slresult['records'] as $user) {
                    ddd("user: " . json_encode($user). "\n\n");
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

    private function getShopInfo($shopId) 
    {
        try {

            $sql = 'SELECT 
                    shops.`name` as `name`, 
                    shops.`abbreviation` as `abbreviation`, 
                    shops.`enabled` as `enabled`
                FROM shops
                WHERE
                    shops.`id` = :shopId';
            
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":id", $shop, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            //throw error if shop is not enabled

            if (isset($res['id'])) {
                $shop = $res;

                $sql = "SELECT 
                        setting_key, `value`
                    FROM shops_settings 
                    WHERE 
                        shop_id = :shopId
                    AND 
                        setting_key IN ('basecurrency', 'currencyexchangerate', 'displaycurrency')";
                $stmt = $this->adminpdo->prepare($sql);
                $stmt->bindValue(":id", $shop, PDO::PARAM_STR);
                $stmt->execute();
    
                $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $aux = explode($res['setting_key']);
                $shop['basecurrency'] = str_replace("'", "", $aux[2]);
                
                $sql = "SELECT 
                        setting_key 
                    FROM shops_settings 
                    WHERE 
                        shop_id = :shopId
                    AND 
                        setting_key='currencyexchangerate'";
                $stmt = $this->adminpdo->prepare($sql);
                $stmt->bindValue(":id", $shop, PDO::PARAM_STR);
                $stmt->execute();
    
                $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $aux = explode($res['setting_key']);
                $shop['currencyexchangerate'] = str_replace("'", "", $aux[2]);

                $sql = "SELECT 
                        setting_key 
                    FROM shops_settings 
                    WHERE 
                        shop_id = :shopId
                    AND 
                        setting_key='displaycurrency'";
                $stmt = $this->adminpdo->prepare($sql);
                $stmt->bindValue(":id", $shop, PDO::PARAM_STR);
                $stmt->execute();
    
                $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $aux = explode($res['setting_key']);
                
            }

            throw new ShopDoesNotExist();


        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }

    }

    private function canRedeem($endUser) 
    {
        if($endUser['disable_redemptions__c']) {
            return false;
        }
        return true;
    }

    private function getProduct($productId)
    {
        try {
        
            $sql = "
                SELECT 
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
                    games.id = :id
            ";

            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":id", $shop, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            if (isset($res['id'])) {
                $product = $res;
                $product['price'] = $this->getProductPrice($product);
                return $product;
            }

            throw new ProductDoesNotExist();

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function getProductPrice($product, $territory, $shop)
    {

        $sql = "SELECT tax, transactionFee, margin, discount FROM margins WHERE provider=:provider";
        $stmt = $this->adminpdo->prepare($sql);
        $stmt->bindValue(":provider", 'Nexway', PDO::PARAM_STR);
        $stmt->execute();

        $margins = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $costPrice = $price * 0.7;
        if ((int)$price === 0 || (int)$costPrice === 0) {
                    $margin = 1;
                }
                else {
                    $margin = $price / $costPrice;
                }
                $priceAccel = 1;

                $margin = $margin;
                $priceAccel = $priceAccel;

                $coins = ($costPrice * $priceAccel) * $margin *
                         (float)$matrixByCurrency[strtolower($shop['basecurrency'])] *
                         (float)$shop['currencyexchangerate'];
                //$coins = round($coins);
                if($allowDecimals === true) {
                    $coins = round($coins, 2);
                } else {
                    $coins = round($coins);
                }

                return array(
                    'feed_price'           => round(($costPrice * $priceAccel) * $margin, 2),
                    'feed_currency'        => strtoupper($currency),
                    'cost_price'           => $costPrice,
                    'transaction_price'    => round(($costPrice * $priceAccel) * $margin * $matrixByCurrency[strtolower($shopSettings['basecurrency'])], 2),
                    'transaction_currency' => $shopSettings['basecurrency'],
                    'currencyexchangerate' => (float)$shopSettings['currencyexchangerate'],
                    'coins'                => $coins
                );

    }

    private function generateOrderId($userId)
    {
        $abbr = 'gms';

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

    private function download($orderId, $product, $user, $shop) {

        $log = '';

        $logger = new ShopLogger($this->tfcpdo, $shop);

        $country    = 'US';
        $secret     = $this->_auth[$country];

        $data = [
            'orderId' => $orderId,
            'contentType' => 'games',
            'product' => $product,
            'user' => $user,
            'shop' => $shop
        ];

        //create new order request
        $request = [
            'secret' => $secret,
            'request' => [
                'partnerOrderNumber' => $orderId,
                'marketingProgramId' => null,
                'orderDate' => date('Y-m-d\TH:i:sP'),
                'currency' => $shop['basecurrency'],
                'customer' => [
                    'locationInvoice' => [
                        'title'       =>  1,
                        'firstName'   =>  'Reward',
                        'lastName'    =>  'World',
                        'address1'    =>  'unknown',
                        'companyName' =>  null,
                        'zipCode'     =>  null,
                        'city'        =>  $user['city__c'],
                        'country'     =>  $user['geo_id__c']
                    ],
                    'email'     =>  $user['Id'] . '@email.com',
                    'language'  =>  'en_XE'
                ],
                'orderLines' => [
                    'createOrderLinesType' => [
                        0 => [
                            'vatRate' => null,
                            'amountTotal' => null,
                            'amountDutyFree' => null,
                            'productRef' => $product['provider_product_id'],
                            'quantity' => 1
                        ]
                    ]
                ]
            ]
        ];

        $data['tfcApiData'] = array(
            'secret' => $secret,
            'request' => $request,
        );
        
        //make request
        $response = $this->makeRequest($this->_apiUrl, $data);

        if (is_string($response)) {
            $us = unserialize($response);
            if ($us) {
                $response = $us;
                unset($us);
            } else {
                $error = $response;
                $response = array(
                    'logs' => array(
                        'error' => array(
                            'api' => $error
                        )
                    )
                );
            }
        }

        if (isset($response['logs'])) {
            if (isset($response['logs']['error'])) {
                throw new ProviderError($message);
                    $this->disableItem($data['provider_product_id']);
            }
            return false;
        }

        $orderLine  = unserialize($response['orderLine']);
        $result     = unserialize($response['result']);

        // Serial numbers
        $data['remark'] = '';
        $data['additional_info'] = '';
        if(strtolower($data['publisher']) != 'microsoft') {
            $i = 1;
            foreach($orderLine->lineItems->createLineItemResponseType as $lineItem) {
                if (isset($lineItem->serials->createSerialResponseType)) {
                    foreach($lineItem->serials->createSerialResponseType as $serial) {
                        $data['additional_info'] .= 'Serial number ' . $i . ': ' . $serial->data . "\n";
                        $data['serial'] = $serial->data;
                        $i++;
                    }
                }
            }
            $data['additional_info'] = trim($data['additional_info']);
        } else {
            $data['remark'] = $orderLine->remark;
        }

        // Download url or steam download
        if(!empty($result->out->downloadManager)) {

            // Check for MAC
            $mac = false;
            if(!empty($data['os'])) {
                foreach($data['os'] as $os) {
                    if($os['name'] == 'mac') {
                        $mac = true;
                        break;
                    }
                }
            }

            // Download url
            if($mac && !empty($result->out->downloadManager->mac)) {
                $data['download_url'] = $result->out->downloadManager->mac;
            } elseif(!$mac && !empty($result->out->downloadManager->pc)) {
                $data['download_url'] = $result->out->downloadManager->pc;
            } else {
                throw new ProviderError('Cannot find download manager file, download manager content: ' . print_r($result->out->downloadManager, true));
                
                // If item can't be downloaded, we disable it to avoid further errors
                $this->disableItem($data['provider_product_id']);

                return false;
            }
        } else {
            $data['download_url'] = false;
        }

        // Add order to download history
        $data['content_type'] = $content;

        // If Content voucher is present we set Transaction TFC to 0 and store voucher info in Salesforce
        $data['content_voucher_used'] = false;
        if (!empty($data['additional']['content_voucher']) || $data['additional']['content_voucher'] !== false) {
            $data['price']['coins'] = 0;
            $data['content_voucher_used'] = true;
        }

        return $this->addDownloadHistory($data);

    }

    private function makeRequest($url, $data) 
    {

        $tmpData = @unserialize($data);
            if ($tmpData) {
                $data = $tmpData;
            }
            $return = array();
            $secret = $data['tfcApiData']['secret'];
            $request= $data['tfcApiData']['request'];

            try {
                ini_set('soap.wsdl_cache_enabled', 0);
                $client = new SoapClient($url, array('encoding'=>'UTF-8', 'trace'=> true, 'wsdl_cache' => 0, 'exceptions' => true, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS));

                // Stock status
                $result = $client->getStockStatus(array(
                    'secret' => $secret,
                    'request' => array('productRef' => array($data['provider_product_id']))
                ));

                if(!empty($result->out->getStockStatusproductStatusResponseType[0]) && $result->out->getStockStatusproductStatusResponseType[0]->responseCode != '0') {
                    $return['logs']['error']['oos'] = 'Create order error: product out of stock';
                    return $return;
                }

                // Cross up sell
                $result = $client->getCrossUpSell(array(
                    'secret' => $secret,
                    'request' => array(
                        'language' => 'EN',
                        'products' => array(
                            array(
                                'productRef' => $data['provider_product_id'],
                                'quantity' => 1
                            )
                        )
                    )
                ));

                if($result->out->responseCode != '0') {
                    $return['logs']['error']['cus'] = 'Create order error: cross up sell, ' . $result->out->responseMessage;
                    return $return;
                }

                // Create order
                $result = $client->create($request);

                if($result->out->responseCode != '0') {
                    $return['logs']['error']['coe'] = 'Create order error: ' . $result->out->responseMessage;
                    return $return;
                }

                // Get data
                if(empty($result->out->orderLines->createOrderLineResponseType[0])) {
                    $result = $client->getData(array(
                        'secret' => $secret,
                        'request' => array(
                            'partnerOrderNumber' => $data['order_id']
                        )
                    ));

                    if($result->out->responseCode != '0') {
                        $return['logs']['error']['coe'] = 'Create order error: get data';
                        return $return;
                    }
                }

                $orderLine = $result->out->orderLines->createOrderLineResponseType[0];

                // New download end date
                $data['download_end_date'] = $orderLine->dateEndDownload;
                $downloadEndDate = $client->updateDownloadTime(array(
                    'secret' => $secret,
                    'request' => array(
                        'partnerOrderNumber' => $data['order_id'],
                        'value' => date('Y-m-d', strtotime('+1 year'))
                    )
                ));

                if($downloadEndDate->out->responseCode == '0') {
                    $data['download_end_date'] = $downloadEndDate->out->newDownloadEndDate;
                }

                $return['logs']['ok']   = 'Order ID: ' . $data['order_id'] . print_r($result, true);
                $return['orderLine']    = serialize($orderLine);
                $return['result']       = serialize($result);
                return $return;

            } catch(SoapFault $e) {
                $return['logs']['error']['soap'] = 'Soap error: ' . (string)$e;
                return $return;
            }

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

        $transactionEquivalent = $data['price']['transaction_price'];
        if(!empty($data['price']['currencyexchangerate'])) {
            $transactionEquivalent = $data['price']['coins'] / $data['price']['currencyexchangerate'];
        }
        $transactionEquivalent = $data['price']['transaction_equivalent'] = round($transactionEquivalent, 4);

        $platformInfo = 'API';

        $userEmail = $data['user']['email__c'];

        // We don't want to store the user email for SGI shops
        // if (($this->auth->getLogged('client_id__c') == 41) || ($this->auth->getLogged('client_id__c') == 43) || ($this->auth->getLogged('client_id__c') == 50) || ($this->auth->getLogged('client_id__c') == 52) || ($this->auth->getLogged('client_id__c') == 54)) {
        //     $userEmail = '';
        // }

        $srpPriceBeforeDiscount = $data['actual_price']['feed_price'];
        if(empty($srpPriceBeforeDiscount)) {
            $srpPriceBeforeDiscount = 0;
        };
        
        $fieldset = array(
            // Info
            'Category_ID__c'            => substr($data['content_type'], 0, 50),
            'content_id__c'             => $data['provider_product_id'],
            'content_title__c'          => substr($data['title'], 0, 250),
            'Content_type__c'           => substr($data['sf_content_type'], 0, 50),
            'product_image__c'          => $this->getImage($data, $data['type'], "model"),
            'isrc__c'                   => substr($data['isrc'], 0, 50),
            'artist__c'                 => substr($data['artist'], 0, 100),
            'order_id__c'               => substr($data['order_id'], 0, 200),
            'TFC_User__c'               => $this->auth->getLogged('Id'),
            'Email__c'                  => $userEmail,
            'Feed_provider__c'          => substr($this->_provider, 0, 50),
            'Label_publisher_name__c'   => substr($data['publisher'], 0, 100),
            'Shop_abbr__c'              => substr(strtoupper($data['shop']['abbreviation']), 0, 4),
            'username__c'               => substr($this->auth->getLogged('login__c'), 0, 50),
            'User_territory__c'         => substr($this->auth->getLogged('geo_id__c'), 0, 5),
            'download_end_date__c'      => $data['download_end_date'],
            'Additional_info__c'        => substr($data['additional_info'], 0, 32768),
            'download_url__c'           => substr($data['download_url'], 0, 1500),
            'content_voucher__c'        => substr($data['external_url'], 0, 1500),
            'voucher_code__c'           => substr($data['voucher_code'], 0, 250),
            // Prices
            'Cost__c'                       => $data['price']['feed_price'],
            'currency__c'                   => $data['price']['feed_currency'],
            'purchasing_price__c'           => $data['price']['cost_price'],
            'transaction_equivalent__c'     => $transactionEquivalent,
            'client_currency__c'            => $data['price']['transaction_currency'],
            'Clubcoins__c'                  => $data['price']['coins'],
            'Platform__c'                   => $platformInfo['compactName'],
            'ThreatMetrix_Score__c'         => $data['tmScore'],
            'ThreatMetrix_Verified__c'      => $data['tmVerified'],
            'gifted__c'                     => $data['gifted'],
            'gift_message__c'               => $data['gift_message'],
            'gift_email__c'                 => $data['gift_email'],
            'serial__c'                     => $data['serial'],
            'newsletter_subscribed__c'      => $data['newsletter_subscribed'],
            'available_clubcoins__c'        => $data['remaining_coins'],
            'first_redemption__c'           => $data['first_redemption'],
            'Special_Offer__c'              => $data['is_promo'],
            'Discount__c'                   => $data['promo_discount'],
            'SRP_Price_Before_Discount__c'  => $srpPriceBeforeDiscount,
            'Category_Voucher_Used__c'      => $data['content_voucher_used'],
            
        );
        
        foreach($fieldset as $k => $v) {
            $fieldset[$k] = htmlspecialchars($v);
        }

        try {
            $result = this->sfClient->insert($fieldset, 'Download_history__c');
            if($result->success == 0) {
                $this->DAO('model', 'logs')
                     ->addStatus('ERROR')
                     ->addMessage('Salesforce error: ' . $result->errors->message)
                     ->logIt();

                return false;
            }

            if(isset($result->id)){
                $data['sf_resultId'] = $result->id;
            }

            // If the download is added to the user's download history we want to clear their cache
            DB::query('UPDATE download_history_cache SET refresh = 1 WHERE user_id = :id', array('id'=>$this->auth->getLogged('Id')));
        } catch(Exception $e) {
            $this->DAO('model', 'logs')
                 ->addStatus('ERROR')
                 ->addMessage('Salesforce error: ' . $e)
                 ->logIt();
        }

        return $data;
    }

}