<?php

namespace App\Helper\Providers;

use PDO;


use TFCLog\TFCLogger;
use App\Handler\Loggers\ShopLogger;
use bjsmasth\Salesforce\CRUD as SFClient;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\String_;
use RTWAPI\RTWAPI;

class Nexway extends Provider
{

    /**
     * Provider name
     *
     * @var string
     */
    protected $_provider = 'Nexway';

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
    protected $_auth = array();

    /**
     * Provider currency by territory
     *
     * @var array
     */
    private $currencyByTerritory = array(
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
    private $languageByTerritory = array(
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

    protected $_url = 'https://api.nexway.store';

    
    /**
     * Download content
     *
     * @param string $content Content name (games|software)
     * @param array $data Product data
     * @return mixed
     */
    public function download($content, $data)
    {

        $country    = $data['shop']['mappedTerritory']['mapped_code'];
        $secret     = $this->_auth['auth'][$country];
        $language   = $this->languageByTerritory[$country];
        $geoIdC     = strtoupper($data['user']['geo_id__c']);
        $userId     = $data['user']['Id'];
        
        $clientSecret = $this->_auth['clientSecret'];
        $realmName = $this->_auth['realmName'];

        $request = $this->createRequest($data, $secret);
        $this->mclog("STARTED", "Request data: " . json_encode($data));

        $token = $this->getToken($clientSecret, $realmName, 'client_credentials');
        $this->mclog("STARTED", "Token requested successfully");

        $result = $this->createOrder($request, $token, $secret);
        $this->mclog("STARTED", "Order requested successfully. Order: " . json_encode($result));

        return $result;

    }

    public function getMappedTerritory($country, $category) {
        try {

            $sql = 'SELECT 
                    mt.catalogue as `catalogue`,
                    t2.code as `mapped_code`,
                    t2.territory_name as `mapped_name`,
                    t1.code as `code`,
                    t1.territory_name as `name`
                FROM catalogue_mapped_territory as mt
                JOIN territories t1 ON mt.territory_id = t1.territory_id
                JOIN territories t2 ON mt.territory_mapped_id = t2.territory_id
                WHERE
                    t1.`code` = :country AND mt.catalogue= :category ';
            
            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":country", $country, PDO::PARAM_STR);
            $stmt->bindValue(":category", $category, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $res['currency'] = $this->currencyByTerritory[$res['mapped_code']];
            $res['language'] = $this->languageByTerritory[$res['mapped_code']];

            return $res;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function createRequest($data, $secret) 
    {
        $request = [
            'partnerOrderNumber' => $data['orderId'],
            'orderDate' => date('Y-m-d\TH:i:sP'),
            'marketingProgramId' => null,
            'currency' => strtoupper($this->currencyByTerritory[$data['shop']['mappedTerritory']['mapped_code']]),
            'orderLines' => [
                0 => [
                    'productRef' => $data['product']['provider_product_id'],
                    'quantity' => 1,
                    'vatRate' => null,
                    'amountDutyFree' => null,
                    'amountTotal' => null
                ]
                ],
            'customer' => [
                'partnerId' => $data['user']['Id'],
                'email'     =>  $data['user']['Id'] . '@email.com',
                'language'  =>  $this->languageByTerritory[$data['shop']['mappedTerritory']['mapped_code']],
                'locationDelivery' => [
                    'title'       =>  1,
                    'firstName'   =>  'Reward',
                    'lastName'    =>  'World',
                    'address1'    =>  'unknown',
                    'companyName' =>  null,
                    'zipCode'     =>  null,
                    'city'        =>  ((empty($data['user']['city__c']))?"City":$data['user']['city__c']),
                    'country'     =>  $data['user']['geo_id__c'],
                    'phone'       => null,
                    'fax'         => null
                ],
                'locationInvoice' => [
                    'title'       =>  1,
                    'firstName'   =>  'Reward',
                    'lastName'    =>  'World',
                    'address1'    =>  'unknown',
                    'companyName' =>  null,
                    'zipCode'     =>  null,
                    'city'        =>  ((empty($data['user']['city__c']))?"City":$data['user']['city__c']),
                    'country'     =>  $data['user']['geo_id__c'],
                    'phone'       => null,
                    'fax'         => null
                ],
                'ipV4' => '127.0.0.1',
                'ipV6' => '2001:0db8:0000:85a3:0000:0000:ac1f:8001'                
            ],
            'payment'   => [
                'paymentMethod' => 'External Payment'
            ]
        ];

        return $request;
    }

    private function getToken($clientSecret, $realmName, $grantType = 'client_credentials')
    {

        $url = $this->_url . '/iam/tokens';
        $data= [
            'clientSecret' => $clientSecret,
            'realmName' => $realmName,
            'grantType' =>  $grantType
        ];

        $request = new RTWAPI();
        $request = $request->setUrl($url)
                            ->setHeader("Content-Type: application/json")
                            ->setData($data)
                            ->setPost();
        $response = $request->makeRequest();
        $response = json_decode($response, true);
        
        return $response['access_token'];

    }

    private function createOrder($request, $token, $secret)
    {

        $url = $this->_url . '/connect/order/new';

        $orderRequest = new RTWAPI();
        $orderRequest = $orderRequest->setUrl($url)
                                        ->setToken($token)
                                        ->setAuth('Bearer')
                                        ->setHeader("Content-Type: application/json")
                                        ->setHeader("secret: " . $secret)
                                        ->setData($request)
                                        ->setJson()
                                        ->setPost();
        $response = $orderRequest->makeRequest();
        $response = json_decode($response, true);

        return $response;

    }

    private function mclog($status, $message, $orderId=NULL, $errorType=NULL) {
        $this->logger->status($status);
        $this->logger->message($message);        
        $this->logger->logIt();
    }


}