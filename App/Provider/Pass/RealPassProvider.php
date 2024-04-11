<?php

namespace App\Provider\Pass;

use App\Domain\API\Pass\Pass;
use App\Domain\API\Pass\RedeemedPass;

/* Requests */
use App\Domain\API\Pass\CreateRequest;
use App\Domain\API\Pass\RedeemRequest;
use App\Domain\API\Pass\InvalidateRequest;
use App\Domain\API\Pass\ListRequest;

use PDO;

/* Exceptions */
use App\Domain\API\Pass\Exception\ExpiredPass;
use App\Domain\API\Pass\Exception\IncorrectUser;
use App\Domain\API\Pass\Exception\IncorrectShop;
use App\Domain\API\Pass\Exception\UsedPass;
use App\Domain\API\Pass\Exception\PassDoesNotExist;
use App\Domain\API\Pass\Exception\PassNotYetStarted;
use App\Domain\API\Pass\Exception\PassPermission;
use App\Domain\Request;
use App\Exception\InvalidObjectException;
use App\Exception\UnauthorizedAccountException;

use TFCLog\TFCLogger;
use bjsmasth\Salesforce\CRUD as SFClient;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\String_;

class RealPassProvider implements PassProvider
{
    private $adminpdo;
    private $sfClient;
    private $fallback;

    protected $categories = [
        1 => 'music',
        2 => 'games',
        3 => 'software',
        4 => 'mobile',
        5 => 'abooks',
        6 => 'ebooks',
        7 => 'movies',
        8 => 'emagazines',
        10 => 'giftcards',
        11 => 'newspapers'
    ];

    protected $passCats = [
        1 => 'MusicPass',
        2 => 'GamePass',
        5 => 'AudioBookPass',
        6 => 'eBookPass',
        7 => 'MoviePass',
        8 => 'eMagPass'
    ];

    public function __construct(PDO $adminpdo, SFClient $sfClient)
    {
        $this->adminpdo = $adminpdo;
        $this->sfClient = $sfClient;
    }

    public function create(CreateRequest $request) : array
    {
        try {

            $sql = "CALL CreatePasses(:shopId, :prefix, :accountId, :numPasses, :contentTypeId, :endUser, :startDate, :expDate, :duration, :numVouchers)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":shopId", $request->shop_id, PDO::PARAM_STR);
            $stmt->bindValue(":prefix", $request->prefix, PDO::PARAM_STR);
            $stmt->bindValue(":accountId", $request->account_id, PDO::PARAM_STR);
            $stmt->bindValue(":numPasses", $request->num_passes, PDO::PARAM_STR);
            $stmt->bindValue(":contentTypeId", $request->content_type_id, PDO::PARAM_STR);
            $stmt->bindValue(":endUser", $request->end_user, PDO::PARAM_STR);
            $stmt->bindValue(":expDate", $request->exp_date, PDO::PARAM_STR);
            $stmt->bindValue(":startDate", $request->start_date, PDO::PARAM_STR);
            $stmt->bindValue(":duration", $request->duration, PDO::PARAM_STR);
            $stmt->bindValue(":numVouchers", $request->num_vouchers, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new InvalidObjectException("No passes created.");
            }

            if(count($request->extra)==0) {
                $request->extra = [];
            }

            //create SF Order
            $order = $this->createSFOrder($request->account_id, $request->shop_id, $request->extra);
            $newPasses = [];
            foreach ($res as $pass) {
                $pss = new Pass($pass);

                //add Pass to Order
                $sfpass = $this->createSFPass($order, $request, $pss);
                $newPasses[] = $pss->getData();
            }

            return $newPasses;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function list(ListRequest $request) : array
    {
    }

    public function redeem(RedeemRequest $request) : RedeemedPass
    {
        try {
            
            //check pass is valid on DB
            $passDbResponse = $this->dbRedeem($request->code, $request->end_user, $request->shop_id, $request->redeem);
            $errChck = $this->errorCheck($passDbResponse[0]);

            if ($errChck) {
                throw $errChck;
            }

            //check pass is valid on SF
            $redeemedPass = $this->sfRedeem($passDbResponse, $request->code, $request->end_user, $request->redeem);

            $redeemedPassConstructor = [
                'code'              => $redeemedPass['Name'],
                'content_type_id'   => $redeemedPass['Pass_Type__c'],
                'user_id'           => $redeemedPass['End_User__c'],
                'activation_date'   => $redeemedPass['Activation_Date__c'],
                'expiration_date'   => $redeemedPass['Expiration_Date__c'],
                'duration'          => $redeemedPass['Duration__c'],
                'vouchers'          => $redeemedPass['Vouchers__c']
            ];

            unset($passDbResponse['errCode']);
            
            return new RedeemedPass($redeemedPassConstructor);
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function createSFOrder(String $accountId, int $shopId, array $extra=[]) : string
    {
        try {

            $shopInfo = $this->getShopInfo($shopId);

            //get pricebook
            $pricebook = $this->sfClient->query("Select Id, Price_Book__c FROM PriceBooks_Stores__c WHERE Store__c='" . $shopInfo['Id'] . "'");
            
            if($pricebook['totalSize'] === 1){
                $pricebookId = $pricebook['records'][0]['Price_Book__c'];
            }

            if ($pricebook['totalSize'] !== 1) {
                $pricebook = $this->sfClient->query("Select Id FROM Pricebook2 WHERE Name='EUR Price Book'");
                $pricebookId = $pricebook['records'][0]['Id'];
            }            

            //get Account Info
            $account = $this->sfClient->query("Select Id, Name, CurrencyIsoCode, AllowedShops__c FROM Account WHERE AccountID__c='" . $accountId . "'");

            if ($account['totalSize'] !== 1) {
                throw new InvalidObjectException("no account found on Salesforce.");
            }
            $account = $account['records'][0];

            //get Contract Info
            $sfContract = $this->sfClient->query("Select Id, CurrencyIsoCode FROM Contract WHERE AccountId='" . $account['Id'] . "' AND CurrencyIsoCode='" . $shopInfo['CurrencyIsoCode'] . "'");

           if(isset($extra['app_order']) && $extra['app_order']) {
                $shopInfo['CurrencyIsoCode'] = $account['CurrencyIsoCode'];    
                //check shop is part of allowed shops
                $allowedShops = explode(";", $account['AllowedShops__c']);
                if(!in_array($shopInfo['shop_name__c'], $allowedShops)) {
                    throw new UnauthorizedAccountException("Account not authorized to create product");
                }
                $sfContract = $this->sfClient->query("Select Id, CurrencyIsoCode FROM Contract WHERE AccountId='" . $account['Id'] . "' AND CurrencyIsoCode='" . $shopInfo['CurrencyIsoCode'] . "' AND Store__c='" . $shopInfo['Id'] . "'");
            }
            

            if(!isset($sfContract['records'][0])) {
                //create contract
                $contract = [
                    'AccountId'         => $account['Id'],
                    'CurrencyIsoCode'   => $shopInfo['CurrencyIsoCode'],
                    'StartDate'         => date('Y-m-d'),
                    'ContractTerm'      => 12,
                    'Store__c'         => $shopInfo['Id'],
                    //'Status'            => 'Activated'
                ];
                $this->sfClient->create("Contract", $contract);
                $sfContract = $this->sfClient->query("Select Id, CurrencyIsoCode FROM Contract WHERE AccountId='" . $account['Id'] . "' AND CurrencyIsoCode='" . $shopInfo['CurrencyIsoCode'] . "' AND Store__c='" . $shopInfo['Id'] . "'");
            } 
            
            $contract = $sfContract['records'][0];

            $order = [
                'AccountId' => $account['Id'],
                'Pricebook2Id' => $pricebookId,
                'Status' => 'Draft',
                'ContractId' => $contract['Id'],
                //'CurrencyIsoCode' => 'EUR',
                'EffectiveDate' => date('Y-m-d'),
                'Description' => 'Pass Order ' . date('d/m/Y'),
                'CurrencyIsoCode' => $shopInfo['CurrencyIsoCode']
            ];

            if(!empty($extra)) {
                $orderExtras = [
                    'Order_Fee__c' => ((isset($extra['fee'])?$extra['fee']:"")),
                    'Order_Discount__c' => ((isset($extra['discount'])?$extra['discount']:"")),
                    'App_Order__c' => ((isset($extra['app_order'])?$extra['app_order']:"")),
                    'App_Order_ID__c' => ((isset($extra['order_id'])?$extra['order_id']:"")),
                    'PoNumber' => ((isset($extra['po_number'])?$extra['po_number']:"")),
                    'Order_Item__c' => ((isset($extra['order_item'])?$extra['order_item']:"")),
                    'Quantity__c' => ((isset($extra['quantity'])?$extra['quantity']:"")),
                    'Unit_Price__c' => ((isset($extra['unit_price'])?$extra['unit_price']:"")),
                    'Order_Total__c' => ((isset($extra['order_total'])?$extra['order_total']:"")),
                    'Contact_Name__c' => ((isset($extra['contact_id'])?$extra['contact_id']:"")),
                    'Order_Codes_Start_Date__c' => ((isset($extra['order_codes_start_time'])?$extra['order_codes_start_time']:"")),
                    'Order_Codes_Expire_Date__c' => ((isset($extra['order_codes_expire_time'])?$extra['order_codes_expire_time']:""))
                ];

                $order = array_merge($order, $orderExtras);
            }

            //create Order
            return $this->sfClient->create('Order', $order);
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function createSFPass(String $orderId, CreateRequest $request, Pass $pass) : string
    {
        try {

            //get currency
            $shop = $this->sfClient->query("Select ID, Name, CurrencyIsoCode FROM Client__c WHERE Name='" . $request->shop_id . "'");
            $shop = $shop['records'][0];
            $currency = $shop['CurrencyIsoCode'];
            $shopId = $shop['Name'];

            if(isset($request->extra['app_order']) && $request->extra['app_order']) {
                //get Account Info
                $account = $this->sfClient->query("Select Id, Name, CurrencyIsoCode FROM Account WHERE AccountID__c='" . $request->account_id . "'");
                if ($account['totalSize'] !== 1) {
                    throw new InvalidObjectException("no account found on Salesforce.");
                }
                $account = $account['records'][0];
                    $shopInfo['CurrencyIsoCode'] = $account['CurrencyIsoCode'];   
                $currency = $shopInfo['CurrencyIsoCode']; 
            }

            

            $productName = $this->passCats[$request->content_type_id] . $currency . $request->duration;

            //get product
            $product = $this->sfClient->query("Select Id FROM Product2 WHERE ProductCode='" . $productName . "'");

            if ($product['totalSize'] < 1) {
                throw new InvalidObjectException("no " . $productName . " product on Salesforce");
            }

            $product = $product['records'][0];

            //get pricebook code
            $pricebook = $this->sfClient->query("Select Id, Price_Book__c FROM PriceBooks_Stores__c WHERE Store__c='" . $shop['Id'] . "'");
            if($pricebook['totalSize'] === 1){
                $pricebookId = $pricebook['records'][0]['Price_Book__c'];
            }

            if ($pricebook['totalSize'] !== 1) {
                $pricebook = $this->sfClient->query("Select Id FROM Pricebook2 WHERE Name='" . $currency . " Price Book'");
                $pricebookId = $pricebook['records'][0]['Id'];
            }

            //get pricebookEntry
            $pricebookEntry = $this->sfClient->query("Select Id, UnitPrice FROM PricebookEntry WHERE Pricebook2Id='" . $pricebookId . "' AND Product2Id='" . $product['Id'] . "'");

            if ($pricebookEntry['totalSize'] !== 1) {
                throw new InvalidObjectException("No Pricebookentry " . $productName . " on Salesforce");
            }

            $pricebookEntry = $pricebookEntry['records'][0];

            if(isset($request->extra['unit_price'])) {
                //this will have to be modified to adjust to shop conversions
                $pricebookEntry['UnitPrice'] = $request->extra['unit_price'];
            }

            //get Account Info
            $account = $this->sfClient->query("Select Id, Name FROM Account WHERE AccountID__c='" . $request->account_id . "'");

            if ($account['totalSize'] !== 1) {
                throw new InvalidObjectException("Account does not exist on Salesforce");
            }

            $account = $account['records'][0];

            //get contact
            $contact = $this->sfClient->query("Select Id FROM Contact WHERE Email = '" . $request->contact . "' AND AccountId='" . $account['Id'] . "'");

            if ($contact['totalSize'] < 1) {
                throw new InvalidObjectException("Contact does not exist on Salesforce.");
            }

            $contact = $contact['records'][0];

            //create OrderItem
            $orderItem = [
                'Product2Id' => $product['Id'],
                'PricebookEntryId' => $pricebookEntry['Id'],
                'OrderId' => $orderId,
                'UnitPrice' => $pricebookEntry['UnitPrice'],
                'Quantity' => 1,
                'Account_Number__c' => $account['Id'],
                'Contact__c' => $contact['Id'],
                'Description' => $pass->code,
                'ServiceDate' => date("Y-m-d", strtotime($pass->start_time)),
                'EndDate' => date("Y-m-d", strtotime($pass->exp_time))
            ];

            $orderItemId = $this->sfClient->create('OrderItem', $orderItem);

            //create Pass
            $pass = [
                'Name' => $pass->code,
                'OwnerAccount__c' => $account['Id'],
                'Start_date__c' => $pass->start_time,
                'Expiration_Date__c' => $pass->exp_time,
                'Duration__c' => $pass->duration,
                'Pass_Type__c' => $this->getCategory($pass->content_type_id)
            ];

            return $this->sfClient->create('Pass__c', $pass);
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function getCategory(int $contentTypeId) : string
    {
        try {
            return $this->categories[$contentTypeId];
        } catch (\Exception $e) {
            return 'all';
        } catch (\Error $e) {
            return 'all';
        }
    }

    private function dbRedeem(String $code, String $endUserId, int $shopId, int $redeem) : array
    {
        try {
            $sql = "CALL RedeemPass(:code, :endUserId, :shopId, :redeem)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":code", $code, PDO::PARAM_STR);
            $stmt->bindValue(":endUserId", $endUserId, PDO::PARAM_STR);
            $stmt->bindValue(":shopId", $shopId, PDO::PARAM_STR);
            $stmt->bindValue(":redeem", $redeem, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new InvalidObjectException("RedeemPass DB function failed");
            }
            return $res;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function errorCheck(array $passDbResponse)
    {
        if (isset($passDbResponse['errCode'])) {
            switch ($passDbResponse['errCode']) {
                case 0:
                    return false;
                    break;
                case 1:
                    return new PassDoesNotExist();
                    break;
                case 2:
                    return new IncorrectUser("This pass was not created for you.");
                    break;
                case 3:
                    return new PassNotYetStarted("Pass has not yet started. Will be Available on " . $passDbResponse['startDate']);
                    break;
                case 4:
                    return new ExpiredPass("Pass has expired on " . $passDbResponse['expDate']);
                    break;
                case 5:
                    return new UsedPass();
                    break;
                case 6:
                    return new IncorrectShop("Incorrect shop selected. Correct Shop: " . $passDbResponse['shopId']);
                    break;
            }
        }
        return false;
    }

    private function sfRedeem(array $passdbResponse, string $passCode, string $enduserId, int $redeem) : array
    {
        try {
            $vouchers = $this->formatVouchersForSF(array_reverse($passdbResponse));

            //check Pass has not been redeemed on SF
            $sfPasss = $this->sfClient->query("Select Id, Name, Duration__c, Pass_Type__c, Expiration_Date__c FROM Pass__c WHERE Name='" . $passCode . "'");
            
            if ($sfPasss['totalSize'] !== 1) {
                throw new UsedPass();
            }

            $passInfo = $sfPasss['records'][0];

            $sfEndUser = $this->sfClient->query("Select Id, email__c, client_lookup__c FROM TFC_User__c WHERE Id='" . $enduserId . "'");
            
            $endUserinfo = $sfEndUser['records'][0];

            //create Pass
            $pass = [
                'Name' => $passInfo['Name'],
                'End_User__c' => $enduserId,
                'Activation_Date__c' => date('Y-m-d'),
                'Duration__c' => $passInfo['Duration__c'],
                'Pass_Type__c' => $passInfo['Pass_Type__c'],
                'Vouchers__c' => $vouchers,
                'Shop_ID__c' => $endUserinfo['client_lookup__c'],
                'User_Email__c' => $endUserinfo['email__c'],
                'Pass_id__c' => $passInfo['Id']
            ];
            
            if($redeem) {

                $this->sfClient->create('Redeemed_Pass__c', $pass);

            }

            $pass['Expiration_Date__c'] = $passInfo['Expiration_Date__c'];

            return $pass;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function formatVouchersForSF(array $passdbResponse) : string
    {
        $ret = '<ol>';

        foreach ($passdbResponse as $passVoucher) {
            $startMonth = date('M', strtotime($passVoucher['start_time']));
            $endMonth = date('M', strtotime($passVoucher['exp_time']));
            $startYear = date('Y', strtotime($passVoucher['start_time']));
            $endYear = date('Y', strtotime($passVoucher['exp_time']));
            $date = $startMonth . ' ' . $startYear;
            if ($startMonth!==$endMonth) {
                if ($startYear!==$endYear) {
                    $date = $startMonth . ' ' . $startYear . '/' .  $endMonth . ' ' . $endYear;
                }
                $date = $startMonth . '/' .  $endMonth . ' ' . $endYear;
            }
            
            $ret .= '<li>Redeem code <strong>' . $passVoucher['code'] . '</strong> during <strong>' . $date . '</strong></li>';
        }

        $ret .= '</ol>';

        return $ret;
    }

    private function getShopInfo(int $shopId) : array
    {
        $sfShop = $this->sfClient->query("Select Id, shop_name__c, shop_abbr__c, CurrencyIsoCode FROM Client__c WHERE Name='" . $shopId . "'");
        return $sfShop['records'][0];
    }

    public function invalidate(InvalidateRequest $request) : bool
    {
        //ToDo: invalidate Pass
        return true;
    }

    public function validShop(Request $request) : bool
    {
        //ToDo: Check Shop is valid
        return true;
    }

    public function validPermission(Request $request) : bool
    {
        //ToDo: validate permissions
        return true;
    }

    public function validPassFormat(Request $request) : bool
    {
        //ToDo: validate Pass format
        return true;
    }


    /**
     * Private methods
     */

    private function createFromRequest(CreateRequest $request) : Pass
    {
        $passContent = json_decode((String)$request, true);

        $i=0;
        while ($i>$passContent['num_Passes']) {
        }

        $passCode = $passContent["prefix"] . strtoupper(uniqid());

        return new Pass(
            [
                "shop_id"           => "shop_id",
                "account_id"        => "account_id",
                "code"              => "code",
                "points"            => "points",
                "content_type_id"   => "content_type_id",
                "pass_type"         => "pass_type",
                "user_id"           => "user_id",
                "status"            => "status",
                "gen_time"          => "gen_time",
                "session_id"        => "session_id",
                "use_time"          => "use_time",
                "generator_id"      => "generator_id",
                "exp_date"          => "exp_date",
                "start_date"        => "start_date",
                "api_user_id"       => "api_user_id",
                "timestamp"         => "timestamp",
            ]
        );
    }


    /**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param PassProvider $fallback
     */
    public function attach(PassProvider $fallback)
    {
        $this->fallback = $fallback;
    }
}
