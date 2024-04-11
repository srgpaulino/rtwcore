<?php

namespace App\Provider\EndUser;

use App\Domain\Base\EndUser;
use \PDO;
use App\Exception\InvalidObjectException;
use App\Exception\InvalidLogException;
use bjsmasth\Salesforce\CRUD;
use TFC\Library_TfcApi as TFCApi;


class PDOEndUserProvider implements EndUserProvider
{
    private $adminpdo;
    private $tfcpdo;
    private $logpdo;
    private $sfclient;
    private $fallback;

    //data structure for EndUser object
    private $objKeys = [
        'id'            => 'id',
        'TFCUserId'     => 'TFCUserId',
        'clientUserId'  => 'clientUserId',
        'accountId'     => 'accountId',
        'loginName'     => 'loginName',
        'name'          => 'name',
        'lastName'      => 'lastName',
        'coinsCount'    => 'coinsCount',
        'geoId'         => 'geoId',
        'language'      => 'language',
        'email'         => 'email',
        'shop'          => 'shop'
    ];

    //data structure for EndUsers Collection
    private $colKeys = [
        'id'            => 'id',
        'loginName'     => 'loginName',
        'TFCUserId'     => 'TFCUserId',
        'lastLogin'     => 'lastLogin',
        'lastIP'        => 'lastIP'
    ];

    //salesforce fields
    private $sfFields = [
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

    private $mode = [
        'single'        => 'objKeys',
        'collection'    => 'colKeys'
    ];

    /**
     * 
     */
    public function __construct(PDO $adminpdo, PDO $tfcpdo, PDO $logpdo, CRUD $sfclient)
    {
        $this->adminpdo = $adminpdo;
        $this->tfcpdo = $tfcpdo;
        $this->logpdo = $logpdo;
        $this->sfclient = $sfclient;
    }

    /**
     * 
     */
    public function getAll(string $filter = null, string $apiUser) : array
    {
        try {
            $sql = "CALL GetAllEndUsers(:filter, :user)";
            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":filter", $filter, PDO::PARAM_STR);
            $stmt->bindValue(":user", $apiUser, PDO::PARAM_STR);
            $stmt->execute();

            return $this->transform(
                $this->mode['collection'],
                $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
            );
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 
     */
    public function getById(string $id, string $apiUser, int $shop = null) : array
    {
        try {
            $result = null;

            $sql = "CALL GetEndUserById(:id)";
            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":id", $id, PDO::PARAM_STR);
            $stmt->execute();

            $res = $this->transform(
                $this->mode['single'],
                $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
            );

            $session = $res['data'][0]['session'];

            //get user info
            $slresult = $this->sfclient->query("SELECT " . implode(',', $this->sfFields) . " FROM TFC_User__c WHERE Id = '" . $res['data'][0]['salesforce_id'] . "'");

            foreach ($slresult['records'] as $enduser) {
                $result = [
                        'data'  =>  [
                        'TFCUserId'     => $enduser['Id'],
                        'clientUserId'  => (!isset($enduser['external_user_id__c']) ? null : $enduser['external_user_id__c']),
                        'loginName'     => $enduser['login__c'],
                        'name'          => $enduser['name__c'],
                        'lastName'      => (!isset($enduser['last_name__c']) ? null : $enduser['last_name__c']),
                        'coinsCount'    => $enduser['coins_count__c'],
                        'geoId'         => (!isset($enduser['geo_id__c']) ? null : $enduser['geo_id__c']),
                        'language'      => (!isset($enduser['language__c']) ? null : $enduser['language__c']),
                        'email'         => $enduser['email__c'],
                        'shop'          => $enduser['client_id__c']
                    ]
                ];
            }

            //get shop info
            $slresult2 = $this->sfclient->query("SELECT Id, shop_abbr__c, Name FROM Client__c WHERE Name = '$shop'");
            foreach ($slresult2['records'] as $shop) {
                $result['data']['shopRef'] = substr($shop['shop_abbr__c'], 0, 3);
                $shopId = $shop['Id'];
            }

            $sql = "SELECT `name`, `prod`, `dev`, `stg` FROM `shop` WHERE `shop_id` = :shopId";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":shopId", $shop['Name'], PDO::PARAM_STR);
            $stmt->execute();

            $res = $this->transform(
                $this->mode['single'],
                $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
            );

            $shopInfo = $res['data'][0];

            //get account info
            //SELECT Id, AccountID__c FROM Account WHERE Client_shop__c = 'a0920000002yVq8AAE' LIMIT 1
            $slresult3 = $this->sfclient->query("SELECT Id, Name, AccountID__c FROM Account WHERE Client_shop__c = '$shopId' LIMIT 1");
            foreach ($slresult3['records'] as $account) {
                $result['data']['accountId'] = $account['AccountID__c'];
            }

            //if((string)env('APP_ENV') === 'prod') {
                //return url
                $result['data']['loginurl'] = "https://".$shopInfo[(string)env('APP_ENV')]."/sso/" . $session;
            //}

            /*if ((string)env('APP_ENV') === 'dev') {
                //return url
                $result['data']['loginurl'] = "https://php7films.thefirstclub.com/sso/" . $session;
            }*/

            return $result;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }


    /**
     * 
     */
    public function getBySFId(string $id, string $apiUser = null, int $shop = null) : array
    {
        try {
            $result = null;

            //get user info
            $slresult = $this->sfclient->query("SELECT " . implode(',', $this->sfFields) . " FROM TFC_User__c WHERE Id = '" . $id . "'");

            foreach ($slresult['records'] as $enduser) {
                $result = [
                        'data'  =>  [
                        'RTWUserId'     => $enduser['Id'],
                        'loginName'     => $enduser['login__c'],
                        'name'          => $enduser['name__c'],
                        'lastName'      => (!isset($enduser['last_name__c']) ? null : $enduser['last_name__c']),
                        'coinsCount'    => $enduser['coins_count__c'],
                        'geoId'         => (!isset($enduser['geo_id__c']) ? null : $enduser['geo_id__c']),
                        'language'      => (!isset($enduser['language__c']) ? null : $enduser['language__c']),
                        'email'         => $enduser['email__c'],
                        'shop'          => $enduser['client_id__c']
                    ]
                ];
            }

            return $result;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function getWalletBySFId(string $id, string $apiUser, int $shop = null) : array
    {

        try {
            $result = null;

            //get user info
            $slresult = $this->sfclient->query("SELECT " . implode(',', $this->sfFields) . " FROM TFC_User__c WHERE Id = '" . $id . "'");

            foreach ($slresult['records'] as $enduser) {
                $result = [
                        'data'  =>  [
                        'RTWUserId'     => $enduser['Id'],
                        'loginName'     => $enduser['login__c'],
                        'coinsCount'    => $enduser['coins_count__c'],
                        'shop'          => $enduser['client_id__c']
                    ]
                ];
            }

            return $result;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }

    }

    /**
     * 
     */
    public function samlExists(int $shop, string $apiUser, string $apiKey) : bool
    {
        try {
            //check if database contains samlvalues for this user/shop
            $sql = "CALL samlExists(:shop, :user, :key)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":shop", $shop, PDO::PARAM_STR);
            $stmt->bindValue(":user", $apiUser, PDO::PARAM_STR);
            $stmt->bindValue(":key", $apiKey, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res)===1) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    /**
     * 
     */
    public function getSAMLKey(int $shop, string $apiUser) : array
    {
        try {
            //fetch SAML key
            $sql = "CALL getSAMLKey(:shop, :user)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":shop", $shop, PDO::PARAM_STR);
            $stmt->bindValue(":user", $apiUser, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res)===1) {
                return $res[0];
            }
            throw new InvalidObjectException('SSO Key unreachable.');
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    /**
     * 
     */
    public function userExists(array $userData, int $shop, string $apiUser) : array
    {
        
        try {
           
            if(isset($userData['username'])){
                $slresult = $this->sfclient->query("SELECT " . implode(',', $this->sfFields) . " FROM TFC_User__c WHERE email__c LIKE '" . $userData['email'] . "' AND login__c LIKE '" . $userData['username'] . "' AND client_id__c = ". $shop);
            } else {
                $slresult = $this->sfclient->query("SELECT " . implode(',', $this->sfFields) . " FROM TFC_User__c WHERE email__c LIKE '" . $userData['email'] . "' AND client_id__c = ". $shop);
            }
            

            if ($slresult['totalSize'] === 1) {
                foreach ($slresult['records'] as $user) {
                    ddd("user: " . json_encode($user). "\n\n");
                    return [
                      "username" => $user['login__c'],
                      "salesforce_id" => $user['Id'],
		              "session" => $userData['session']
                    ];
                }
            }
            return [];
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    /**
     * 
     */
    public function createUser(array $userData, int $shop, $apiUser) : array
    {
        try {

            //ToDo: Get client look up id
            $clientLookup = $this->getClientLookup($userData['client_id']);

            $shopUrl = '';
            $shopName = '';

            if(is_array($userData['additional_1'])) {

                if(isset($userData['additional_1']['shop_url'])) {
                    $shopUrl = $userData['additional_1']['shop_url'];
                }
                if(isset($userData['additional_1']['shop_name'])) {
                    $shopName = $userData['additional_1']['shop_name'];
                }

                $userData['additional_1'] = json_encode($userData['additional_1']);
            }

            $data = [
                //'coins_count__c' => $userData['credit'],
                'name__c' => $userData['name'],
                'geo_id__c' => $userData['country'],
                'client_id__c' => $shop,
                //'client_lookup__c' => 'a092L00000BSLpzQAH',
                'client_lookup__c' => $clientLookup,
                'date_of_birth__c' => $userData['date_of_birth'],
                'email__c' => $userData['email'],
                'last_name__c' => $userData['last_name'],
                'login__c' => $userData['username'],
                'Additional_info_1__c' => $userData['additional_1'],
                'Additional_info_2__c' => $userData['additional_2'],
                'Client_Shop_URL__c' => $shopUrl,
                'Client_Shop_Name__c' => $shopName,
                'external_user_id__c' => $userData['external_user_id'],
		        'password__c' => md5($userData['session'])
            ];
            $this->sfclient->create('TFC_User__c', $data);
            
            return $this->userExists($userData, $shop, $apiUser);

            /*$sql = "CALL createUser(:id, :username, :user)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":id", $userData['uid'], PDO::PARAM_STR);
            $stmt->bindValue(":username", $userData['username'], PDO::PARAM_STR);
            $stmt->bindValue(":user", $apiUser, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (count($res!==1)) {
                throw InvalidObjectException();
            }

            return $this->getById($res[0]['id']);*/
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function updateUser(array $userData, $shop, $userId, $apiUser) : array
    {
        try {

            //ToDo: Get client look up id
            $clientLookup = $this->getClientLookup($userData['client_id']);

            $shopUrl = '';
            $shopName = '';

            if(is_array($userData['additional_1'])) {

                if(isset($userData['additional_1']['shop_url'])) {
                   $shopUrl = $userData['additional_1']['shop_url'];
                }
                if(isset($userData['additional_1']['shop_name'])) {
                    $shopName = $userData['additional_1']['shop_name'];
                }

                $userData['additional_1'] = json_encode($userData['additional_1']);
            }

            //TODO: create user on Salesforce
            $data = [
                //'coins_count__c' => $userData['credit'],
                'name__c' => $userData['name'],
                'geo_id__c' => $userData['country'],
                'client_id__c' => $shop,
                //'client_lookup__c' => 'a092L00000BSLpzQAH',
                'client_lookup__c' => $clientLookup,
                'date_of_birth__c' => $userData['date_of_birth'],
                'email__c' => $userData['email'],
                'last_name__c' => $userData['last_name'],
                'login__c' => $userData['username'],
                'Additional_info_1__c' => $userData['additional_1'],
                'Additional_info_2__c' => $userData['additional_2'],
                'Client_Shop_URL__c' => $shopUrl,
                'Client_Shop_Name__c' => $shopName,
                'external_user_id__c' => $userData['external_user_id'],
		        'password__c' => md5($userData['session'])
            ];
	
	    ddd("userId: " . $userId);
	    ddd("data: " . json_encode($data));

            $this->sfclient->update('TFC_User__c', $userId, $data);


            return $this->userExists($userData, $userData['client_id'], $apiUser);

            /*$sql = "CALL createUser(:id, :username, :user)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":id", $userData['uid'], PDO::PARAM_STR);
            $stmt->bindValue(":username", $userData['username'], PDO::PARAM_STR);
            $stmt->bindValue(":user", $apiUser, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (count($res!==1)) {
                throw InvalidObjectException();
            }

            return $this->getById($res[0]['id']);*/
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }


    /**
     * 
     */
    public function registerUserLogin(array $userData, string $apiUser) : string
    {
        try {
            $sql = "CALL registerLogin(:session, :username, :salesforce_id, :user)";
            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":session", $userData['session'], PDO::PARAM_STR);
            $stmt->bindValue(":username", $userData['username'], PDO::PARAM_STR);
            $stmt->bindValue(":salesforce_id", $userData['salesforce_id'], PDO::PARAM_STR);
            $stmt->bindValue(":user", $apiUser, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res)!==1) {
                throw new InvalidLogException();
            }
            return $res[0]['id'];
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    /** private functions **/

    /**
     * 
     */
    //check if the items from fetch are correct and return them or an empty array.
    private function transform(string $mode, array $result) : array
    {
        if (empty($result)) {
            return [];
        }

        switch ($mode) {
            case 'objKeys': // in case of a singular item
                if (!$this->isValidObject($mode, $result)) {
                    $code = 1;
                }
                break;
            case 'colKeys': //in case of a collection of items
                foreach ($result as $index=>$res) {
                    if (!$this->isValidObject($mode, $res)) {
                        unset($result[$index]);
                    }
                }
                if (count($result)===0) {
                    $code = 1;
                }
                break;
        }

        // onitems recovered are in a recognizable format
        /*if($code===1) {
            throw new InvalidObjectException('EndUser object in unrecognizable format.');
        }*/

        //return the items in a data array
        return ['data' => $result];
    }

    /**
     * 
     */
    //verify if the object procured is valid
    private function isValidObject(string $mode, array $result) : bool
    {
        return (array_keys($result) === array_keys($this->$mode));
    }

    /**
     * 
     */
    private function sfUserExists(string $id, string $apiUser) : array
    {
        try {
            $result = null;

            $sql = "CALL GetEndUserById(:id, :user)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":id", $id, PDO::PARAM_STR);
            $stmt->bindValue(":user", $apiUser, PDO::PARAM_STR);
            $stmt->execute();

            $res = $this->transform(
                $this->mode['single'],
                $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
            );

            $slresult = $this->sfclient->query("SELECT " . implode(',', $this->sfFields) . " FROM TFC_User__c WHERE Id = '" . $res['data'][0]['TFCUserId'] . "'");

            foreach ($slresult as $enduser) {
                $sqlobj = $res['data'][0];
                $result = [
                        'data'  =>  [
                        'id'            => $id,
                        'TFCUserId'     => $enduser->Id,
                        'clientUserId'  => (!isset($enduser->external_user_id__c) ? null : $enduser->external_user_id__c),
                        'loginName'     => $enduser->login__c,
                        'name'          => $enduser->name__c,
                        'lastName'      => (!isset($enduser->last_name__c) ? null : $enduser->last_name__c),
                        'coinsCount'    => $enduser->coins_count__c,
                        'geoId'         => (!isset($enduser->geo_id__c) ? null : $enduser->geo_id__c),
                        'language'      => (!isset($enduser->language__c) ? null : $enduser->language__c),
                        'email'         => $enduser->email__c,
                        'shop'          => $enduser->client_id__c
                    ]
                ];
            }

            return $result;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function endUserExists(string $endUser) : bool
    {
        try {

            $slresult = $this->sfclient->query("SELECT " . implode(',', $this->sfFields) . " FROM TFC_User__c WHERE Id = '" . $endUser . "'");

            foreach ($slresult['records'] as $enduser) {
                $result = [
                    'TFCUserId'     => $enduser['Id']
                ];
            }

            if($result['TFCUserId']==$endUser) {
                return true;
            }

            return false;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function checkVoucher($voucherCode, $session, $user) {
	
        if($this->getSFVoucher($voucherCode)) {
            return $user;
        }

        ddd("validating voucher");
        $voucher = $this->validateVoucher($voucherCode);


        if(!isset($voucher[0])){
            // Handle get voucher error here
            return $user;
        }

        $sql = "SELECT `name`, `abbreviation` FROM `shops` WHERE `id` = :shopId";
            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":shopId", $voucher[0]['shop_id'], PDO::PARAM_STR);
            $stmt->execute();

            $res = $this->transform(
                $this->mode['single'],
                $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
            );

            $shop = $res['data'][0];

        $data = array(
            'expire_date__c' => date('Y-m-d', strtotime($voucher[0]['exp_time'])),
            'Name' => $voucherCode,
            'Purchased_clubcoins__c' => (int) $voucher[0]['points'],
            //'TFC_User__c' => $userId,
            'TFC_User__c' => $user['TFCUserId'],
            'shop_name__c' => $shop['name'],
            'shop_abbr__c' => $shop['abbreviation'],
            'category_id__c' => $voucher[0]['content_type_id'],
            'content_id__c' => (isset($voucher[0]['content_id'])?$voucher[0]['content_id']:0),
            'voucher_type__c' => $voucher[0]['voucher_type'],
            'client_order_id__c' => $session
        );

    ddd("adding voucher to SF.");
	$this->sfclient->create('Voucher__c', $data);

    ddd("points before voucher = " . $user['coinsCount']);

    $sql = "CALL RedeemVoucher(:code, :shopId, :redeem)";
    $stmt = $this->adminpdo->prepare($sql);
    $stmt->bindValue(":code", $voucherCode, PDO::PARAM_STR);
    $stmt->bindValue(":shopId", $voucher[0]['shop_id'], PDO::PARAM_STR);
    $stmt->bindValue(":redeem", 1, PDO::PARAM_STR);
    $stmt->execute();

    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $user['coinsCount'] += $voucher[0]['points'];

	$userUpdate = [
		'coins_count__c' => $user['coinsCount']
	];

    ddd("points after voucher = " . $user['coinsCount']);

	$this->sfclient->update('TFC_User__c', $user['TFCUserId'], $userUpdate);

    return $user;
    }

    private function getSFVoucher($voucherCode) {

    	$results = $this->sfclient->query("Select Id FROM Voucher__c WHERE Name='" . $voucherCode . "'");

	    if($results['totalSize'] >0 ) {
            return true;
        }

    	return false;

    }

    private function getClientLookup($clientId) {

        $slresult = $this->sfclient->query("Select Id FROM Client__c WHERE Name='" . $clientId . "'");

        if ($slresult['totalSize'] === 1) {
            foreach ($slresult['records'] as $client) {
                return $client['Id'];
            }
        }

    	return false;

    }

    private function validateVoucher($voucherCode)
    {
	try {
          $result = null;

          $sql = "CALL GetVoucherById(:id)";
          $stmt = $this->adminpdo->prepare($sql);
          $stmt->bindValue(":id", $voucherCode, PDO::PARAM_STR);
          $stmt->execute();

          $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

	  ddd("voucher result: " . json_encode($result));

          return $result;
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
     * @param EndUserProvider $fallback
     */
    public function attach(EndUserProvider $fallback)
    {
        $this->fallback = $fallback;
    }
}
