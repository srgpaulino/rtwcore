<?php

namespace App\Repository\API;

use App\Domain\API\Product\Redemption;
use App\Provider\EndUser\EndUserProvider;
use App\Provider\SFRedemption\SFRedemptionProvider;
use App\Provider\Id\IdProvider;
use App\Provider\APIUser\APIUserProvider;
use App\Provider\Shop\ShopProvider;
use App\Domain\Base\EndUser;
use DateTimeImmutable;
use App\Exception\DoesNotExistException;
use App\Exception\InvalidAttributeException;
use App\Exception\InvalidArgumentException;
use App\Exception\InvalidAuthenticationException;
use App\Exception\InvalidObjectException;
use App\Exception\InvalidErrorException;
use App\Exception\InvalidAPIKeyException;
use App\Exception\InvalidUserException;
use App\Domain\API\Product\Exception\InsufficientCreditException;
use Ramsey\Uuid\Uuid;
use ParagonIE\Halite\HiddenString;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;
use \ParagonIE\EasyRSA\KeyPair;
use TFC\Library_TfcApi as TFCAPI;

class EndUserRepository
{
    private $endUserProvider;
    private $sfRedemptionProvider;

    private $shopProvider;
    private $idProvider;

    private $apiUserProvider;

    private $samlStructure = [
        'firstName' => 'firstName',
        'lastName' => 'lastName',
        'email' => 'email',
        'username' => 'username',
        'dateOfBirth' => 'dateOfBirth',
        'country' => 'country',
        'credit' => 'credit',
        'notifyUser' => 'notifyUser',
        'externalUser' => 'externalUser',
        'additional1' => 'additional',
        'additional2' => 'additional2'
    ];

    private $iv = "1234567890123456";

    public function __construct(EndUserProvider $endUserProvider, SFRedemptionProvider $sfRedemptionProvider, IdProvider $idProvider, APIUserProvider $apiUserProvider, ShopProvider $shopProvider)
    {
        $this->endUserProvider = $endUserProvider;
        $this->sfRedemptionProvider = $sfRedemptionProvider;
        $this->idProvider = $idProvider;
        $this->apiUserProvider = $apiUserProvider;
        $this->shopProvider = $shopProvider;
    }

    public function authenticate(array $enduser, int $shop, string $apiUser, string $apiKey)
    {
        //get item from db by shop and SAMLResponse

        if ($this->samlExists($shop, $apiUser, $apiKey)) {
            return $this->authenticateUser($enduser, $shop, $apiUser);
        }
        //return Invalid Authentication if SAML doesn't exist.
        //Expected HTTP Response 401.
        throw new InvalidAuthenticationException('You are not authorized to use this function.');
    }

    public function getAll(string $filter=null, string $apiUser) : array
    {
        //get content from db by filter
        if ($this->filterIsValid($filter)) {
            try {
                $res = $this->endUserProvider->getAll($filter, $apiUser);
                if (empty($res)) {
                    throw new DoesNotExistException();
                }
                return $res;
            } catch (PDOException $e) {
                throw $e;
            } catch (InvalidObjectException $e) {
                throw $e;
            }
        }
        //return Invalid Argument if filter is in an incorrect format.
        //Expected HTTP Response 422.
        throw new InvalidArgumentException("Invalid filter format.");
    }

    public function getById(string $id, string $apiUser) : array
    {

        //return Invalid Argument if ID is in an incorrect format.
        //Expected HTTP Response 422.
        if (!Uuid::isValid($id)) {
            throw new InvalidArgumentException("Invalid ID format.");
        }

        try {
            $res = $this->endUserProvider->getById($id, $apiUser);
            if (empty($res)) {
                throw new DoesNotExistException();
            }
            return $res;
        } catch (PDOException $e) {
            throw $e;
        } catch (DoesNotExistException $e) {
            throw $e;
        } catch (InvalidObjectException $e) {
            throw $e;
        } catch (Error $e) {
            throw new InvalidErrorException($e->getMessage());
        }
    }


    public function getIdentity(string $userData, int $shop, string $currency, string $apiUser, string $key) : array
    {
        ddd("userData: ");
        ddd($userData);
        ddd("shop: " . $shop);
        ddd("apiUser: " . $apiUser);
        ddd("key: " . $key);

        ddd("checking if key is valid...");
        //check if key is valid
        if(!$this->validateKey($apiUser, $key)) {
            ddd("invalid key");
            throw new InvalidAPIKeyException("API key mismatch.");
        }

        ddd("valid key");

        //check if user exists
        $sfUser = $this->endUserProvider->userExists(json_decode($userData, true), $shop, $apiUser);

        ddd($sfUser);
	    
        if (empty($sfUser)) {
            throw new InvalidUserException('User does not exist.');
        }

        //get user info
        $user = $this->endUserProvider->getBySFId($sfUser['salesforce_id'], $apiUser, $shop);
        $user['base_currency'] = $this->shopProvider->getShopCurrency($shop);
        $user['conversion_rate']['points_per_base'] = $this->shopProvider->getShopRate($shop);
        $user['conversion_rate'][$currency.'_point_value'] = $this->shopProvider->getSHopConversion($currency, $user['base_currency'])/$user['conversion_rate']['points_per_base'];

        return $user;
    }


    public function getWallet(string $userData, string $shop, string $apiUser, string $key) : array 
    {

        //check if key is valid
        if(!$this->validateKey($apiUser, $key)) {
            throw new InvalidAPIKeyException("API key mismatch.");
        }

        //check if userExists
        $sfUser = $this->endUserProvider->userExists(json_decode($userData, true), $shop, $apiUser);

        if (empty($sfUser)) {
            throw new InvalidUserException("User does not exist.");
        }

        //get user info
        $wallet = $this->endUserProvider->getWalletBySFId($sfUser['salesforce_id'], $apiUser, $shop);

        return $wallet;

    }

    public function redeem(string $userData, string $redemption, string $shop, string $apiUser, string $key) 
    {

        //check if key is valid
        if(!$this->validateKey($apiUser, $key)) {
            throw new InvalidAPIKeyException("API key mismatch.");
        }

         //check if userExists
         $sfUser = $this->endUserProvider->userExists(json_decode($userData, true), $shop, $apiUser);

        if (empty($sfUser)) {
           throw new InvalidUserException('User does not exist.');
        }

        $sfUser = $this->endUserProvider->getBySFId($sfUser['salesforce_id']);
        $sfUser = $sfUser['data'];
        $redemption = json_decode($redemption, true);

        $sfUser = $this->redeemItem($redemption, $sfUser);

        //format redemption data
        $sfRedemption = $this->formatRedemption($redemption, $sfUser, $shop);

        //save redemption into Salesforce
        $sfRedemptionId = $this->sfRedemptionProvider->createSFRedemption($sfRedemption);

        //update users points
        $sfUser = $this->sfRedemptionProvider->updateUserPoints($sfUser);

        //check user again
        $sfUser = $this->endUserProvider->userExists(json_decode($userData, true), $shop, $apiUser);
        $sfUser = $this->endUserProvider->getBySFId($sfUser['salesforce_id']);
        $sfUser = $sfUser['data'];

        $return = [
            "user" => $sfUser,
            "redemption" => $redemption
        ];
        return $return;

    }



    /** Private Functions **/

    private function samlExists(int $shop, string $apiUser, string $apiKey) : bool
    {
        try {
            //check if SAML Exists
            return $this->endUserProvider->samlExists($shop, $apiUser, $apiKey);
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function authenticateUser(array $userData, int $shop, string $apiUser, string $iv=null)
    {
        try {
            //decrypt SAMLResponse
            /*$key = $this->endUserProvider->getSAMLKey($shop, $apiUser);
            $userData = $this->decryptSAML($encUserData, $key);*/
            if (isset($userData['user'])) {
                $userData = $userData['user'];
            }
            //check if user user Exists
            $sfUser = $this->endUserProvider->userExists($userData, $shop, $apiUser);

            if (!empty($sfUser)) {
                //signOn existing user
                $user = $this->signOnUser($sfUser, $userData, $apiUser, $shop);
		//$user = $this->updateCredit(json_decode($userData, true), $user['data'], $apiUser);
            } else {
                $newUser = $this->registerUser($userData, $shop, $apiUser);

	        $user = $this->signOnUser($newUser, $userData, $apiUser, $shop);
		//$user = $this->updateCredit(json_decode($userData, true), $userData['user'], $apiUser);
            }


            $user = $this->updateCredit($userData, $user['data'], $apiUser);
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }

        return $user;
    }

    private function decryptSAML(string $encUserData, array $key) : array
    {
        try {
	    switch ($key['encryption']) {
                case 'AES256':
                    //$encUserData = base64_decode($encUserData);
                    $userData = $this->decryptWithAES256($encUserData, $key['key']);
                    break;
                case 'Libsodium':
                    $userData = $this->decryptWithLibsodium($encUserData, $key['key']);
                    break;
                case 'easyRSA':
                    $userData = $this->decryptWithEasyRSA($encUserData, $key['key']);
                    break;
                default:
            }
            return $userData;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function decryptWithAES256(string $encUserData, string $key) : array
    {
        $components = json_decode(base64_decode($encUserData), true);
        $method = $components['method'];
        $salt = $components['salt'];
        $key = hash('sha256', $salt.$key);
        $iv = $components['iv'];
        $encdata = base64_decode($components['encrypt']);

        $ret = openssl_decrypt(
            $encdata,
            $method,
            $key,
            0,
            $iv
        );
        return json_decode($ret, true);
    }

    private function decryptWithLibsodium(string $encUserData, \ParagonIE\Halite\Symmetric\EncryptionKey $key)
    {
        return Symmetric::decrypt($encUserData, $key);
    }

    private function decryptWithEasyRSA(string $encUserData, string $key) : array
    {
        return EasyRSA::decrypt($encUserData, $key);
    }

    private function registerUser(array $userData, int $shop, string $apiUser) : array
    {
        try {
            //validate user info
            $validUser = $this->validateUser($userData);
            //create user
            $endUser = $this->endUserProvider->createUser($validUser, $shop, $apiUser);
	    //$endUser = $this->endUserProvider->getById($newUser['salesforce_id'], $apiUser,  $userData['client_id']);		 
            //return user info
            return $endUser;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function signOnUser(array $sfUser, array $userData, string $apiUser, int $shop) : array
    {
        try {
            //log user login
            $uid = $this->endUserProvider->registerUserLogin($sfUser, $apiUser);
	    $this->endUserProvider->updateUser($userData, $shop, $sfUser['salesforce_id'], $apiUser);
            //fetch user info
            $endUser = $this->endUserProvider->getById($uid, $apiUser, $shop);
            //return user info
            return $endUser;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function filterIsValid(string $filter=null) : bool
    {
        //ToDo: function to validate filter values
        return true;
    }

    private function validateUser(array $userData) : array
    {
        //check userData contains same fields as $samlStructure
        return $userData;
        if (array_keys($userData) === array_keys($this->samlStructure)) {
            return $userData;
        }
        throw new InvalidObjectException('Invalid Userdata sent in SAMLResponse.');
    }

    private function updateCredit(array $userData, array $user, string $apiUser)
    {
	//if userData vouchers is not set, set it
        if (!isset($userData['vouchers'])) {
            $userData['vouchers'] = [];
        }
        //check if userData coin count and user coin count are the same
        if ($userData['credit'] > $user['coinsCount']) {
            array_push(
                $userData['vouchers'],
                [
                    'points'            => ($userData['credit'] - $user['coinsCount']),
                    'ref'               => "API",
                    'account_id'        => $user['accountId'],
                    'exp_time'          => date("Y-m-d h:i:s", strtotime(" +2 months")),
                    'points_exp_time'   => date("Y-m-d h:i:s", strtotime(" +2 months")),
                    'type'              => 1
                ]
            );
        }

        //check userData contains voucher info
        if (!empty($userData['vouchers'])) {
            $user = $this->redeemVouchers($userData['vouchers'], $user, $userData);
        }

        return $user;
    }

    private function redeemVouchers(array $vouchers, array $user, $userData)
    {

        //base info for vouchers being generated
        $info = [
            'shop_id'           => $user['shop'],
            'count'             => 1,
            'ref'               => 'API',
            'account_id'        => $userData['client_id'],
            'start_time'        => date('Y-m-d h:i:s'),
            'points_start_time' => date('Y-m-d h:i:s'),
            'api_user_id'       => 1,
	    'points'		=> 1 
        ];
        foreach ($vouchers as $voucher) {
            //generate voucher
            //$v = $this->generateVoucher($info, $voucher);
	        $user = $this->endUserProvider->checkVoucher($voucher, $userData['session'], $user);
        }

        return $user;
    }

    private function generateVoucher(array $info, $voucher)
    {
        $apiUrl = 'http://vouchers.thefirstclub.net/api/v1/voucher/create';
        $privateKey = '0418273';

        $jsonData = array(
            "generate" => array(
                "shop_id" => $info['shop_id'],
                "points" => $info['points'],
                "count" => 1,
                "ref" => 'API',
                "account_id" => $info['account_id'],
                "exp_time" => date('Y-m-d h:i:s', strtotime("+11 weeks")),
                "start_time" => date('Y-m-d h:i:s'),
                "points_exp_time" => date('Y-m-d h:i:s', strtotime("+11 weeks")),
                "points_start_time" => date('Y-m-d h:i:s'),
                "api_user_id" => 41,
                "content_type_id" => $voucher->contentTypeId,
                "timestamp" => date('c')
            )
        );

        $request = new Library_TfcApi();
        $response = $request->setUser(41)
                            ->setKey($privateKey)
                            ->setData($jsonData)
                            ->setUrl($apiUrl)
                            ->setPost()
                            ->makeRequest();

        //$response = '{"generate":{"shop_id":32,"count":1,"ref":"FED","account_id":"a061a000004o1nkAAA","gen_time":"2016-07-07 18:34:00","exp_time":"2016-09-26 02:48:33","start_time":"2016-07-07 07:33:59","session_id":42,"generator_id":41,"vouchers":[{"code":"FED6064587376902"}],"content_type_id":1,"content_id":0}}';

        $response = json_decode($response, true);
        if ($response === null) {
            // Handle generate voucher error here
            return false;
        }

        if (!isset($response['generate'])) {
            // Handle generate voucher error here
            return false;
        }

        return $response;
    }

    private function validateKey($apiUser, $key) 
    {
        return $this->apiUserProvider->validateKey($apiUser, $key);
    }

    private function formatRedemption($redemption, $user, $shop)
    {
         $redemptionStructure = [
            "content_id__c" => "Content ID",
            "order_id__c" => "Order ID",
            "product_image__c" => "Product Image",
            "content_title__c" => "Content Name",
            "Label_publisher_name__c" => "Network Name",
            "Feed_provider__c" => "Feed Provider",
            "Category_ID__c" => "Category ID",
            "Content_type__c" => "Content Type",
            "Transaction_Status__c" => "Transaction Status",
            "TFC_User__c" => "RTW User",
            "username__c" => "RTW Username",
            "Email__c" => "Email",
            "User_territory__c" => "User Territory",
            "Shop_abbr__c" => "Store Abbr",
            "available_clubcoins__c" => "Available Points",
            "Provider_User_ID__c" => "Provider User ID",
            "Provider_Shop_Name__c" => "Provider Shop Url",
            "currency__c" => "Currency",
            "Cost__c" => "SRP Price",
            "purchasing_price__c" => "Purchasing Price",
            "transaction_price__c" => "Transaction Price",
            "Transaction_Fee__c" => "Transaction Fee",
            "Product_Discount__c" => "Product Discount",
            "Product_Fee__c" => "Product Fee",
            "Clubcoins__c" => "Points Cost",
            "Transaction_Exchange_Rate__c" => "Transaction Exchange Rate",
            "VAT__c" => "VAT",
            "VAT_Value__c" => "VAT Value",
            "Provider_Margin__c" => "Provider Margin",
            "Provider_Margin_Value__c" => "Provider Margin Value",
            "Provider_Discount__c" => "Provider Discount",
            "Provider_Discount_Value__c" => "Provider Discount Value",
            "Special_Offer__c" => "Special Offer",
            "Discount__c" => "Offer",
            "Offer_SRP__c" => "Offer SRP",
            "Offer_Id__c" => "Offer ID",
            "Offer_Name__c" => "Offer Name",
            "Provider_order_number__c" => "Provider Order Number",
            "download_url__c" => "Download URL",
            "Additional_info__c" => "Additional Info"
         ];

         $defaultValues = [
            "Special_Offer__c" => 0
         ];

         $sfRedemption = [];         

         if(isset($redemption['Content ID'])) 
         {
            $redemption['Provider Order Number'] = $redemption['Content ID'];
            if(!is_numeric($redemption['Content ID'])) {
                $redemption['Content ID'] = hexdec(bin2hex($redemption['Content ID']));
            }
         }

         foreach ($redemptionStructure as $key=>$value) 
         {
            if(isset($redemption[$value])) {
                $sfRedemption[$key] = $redemption[$value];

                //calculate mnargin values
                if($key=="Provider_Margin__c")
                {
                    $sfRedemption['Provider_Margin_Value__c'] = $sfRedemption['Cost__c'] * $redemption['Provider Margin'];
                    //calculate purchasing price in case it is not sent but we have margin values
                    if((!isset($redemption['Purchasing Price'])) && !empty($redemption['Provider Margin'])) {
                        $sfRedemption['purchasing_price__c'] = $sfRedemption['Cost__c'] - ($sfRedemption['Cost__c'] * $redemption['Provider Margin']);
                    }
                }

                if($key=="Provider_Margin_Value__c")
                {
                    $sfRedemption['Provider_Margin_Value__c'] = $redemption["Provider Margin Value"];
                    if((!isset($redemption['Purchasing Price'])) && !empty($redemption['Provider Margin Value'])) {
                        $sfRedemption['purchasing_price__c'] = $sfRedemption['Cost__c'] - $redemption['Provider Margin Value'];
                    }
                }

            } else {
                $sfRedemption[$key] = null;
                if(isset($defaultValues[$key])) {
                    $sfRedemption[$key] = $defaultValues[$key];    
                }                
                if($key=="Cost__c" && empty($redemption["SRP Price"])) 
                {
                    $sfRedemption['Cost__c'] = $redemption["Transaction Price"];
                }
            }
         }

         //calculate profit
         $sfRedemption['Profit_Value__c'] = $sfRedemption['Cost__c'] - $sfRedemption['purchasing_price__c'];

         //populate order_id__c
         $sfRedemption['order_id__c'] = $this->createOrderId($redemption);
         $sfRedemption['Transaction_Exchange_Rate__c'] = $this->getExchangeRate($sfRedemption['currency__c'], $shop);
         $sfRedemption = $this->populateUserInfo($user, $sfRedemption);

         return $sfRedemption;

    }

    private function createOrderId(array $redemption)
    {
        return md5(time().$redemption['Content Name']);
    }

    private function populateUserInfo($user, $sfRedemption) 
    {
        
        $userData = $user;

        $sfRedemption['TFC_User__c'] = $userData['RTWUserId'];
        $sfRedemption['username__c'] = $userData['loginName'];
        $sfRedemption['Email__c'] = $userData['email'];
        $sfRedemption['User_territory__c'] = $userData['geoId'];
        $sfRedemption['available_clubcoins__c'] = $userData['coinsCount'];

        return $sfRedemption;
        
    }

    private function redeemItem($redemption, $user)
    {    

        if($redemption['Points Cost']>$user['coinsCount']) 
        {
            throw new InsufficientCreditException('Insufficient points in our account.');
        }

        $user['coinsCount'] -= $redemption['Points Cost'];

        return $user;

    }

    private function getExchangeRate($currency, $shop)
    {
        try {
            //get shop currency
            $shopCurrency = $this->shopProvider->getShopCurrency($shop);

            //convert to shop currency
            $conversion = $this->shopProvider->getShopConversion($currency, $shopCurrency);

            //return result
            return $conversion;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }

    }

}
