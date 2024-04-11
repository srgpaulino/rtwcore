<?php

namespace App\Provider\Voucher;

use App\Domain\API\Voucher\CheckValidityRequest;
use App\Domain\API\Voucher\Voucher;
use App\Domain\API\Voucher\RedeemedVoucher;

/* Requests */
use App\Domain\API\Voucher\CreateRequest;
use App\Domain\API\Voucher\DFActivateRequest;
use App\Domain\API\Voucher\ShopCreateRequest;
use App\Domain\API\Voucher\RedeemRequest;
use App\Domain\API\Voucher\DFRedeemRequest;
use App\Domain\API\Voucher\InvalidateRequest;
use App\Domain\API\Voucher\ListRequest;
use App\Domain\API\Voucher\CodeRequest;
use App\Domain\API\Voucher\VoucherWalletRequest;
use App\Domain\API\Voucher\ListVoucherWalletRequest;

use PDO;

/* Exceptions */
use App\Domain\API\Voucher\Exception\ExpiredVoucher;
use App\Domain\API\Voucher\Exception\IncorrectShop;
use App\Domain\API\Voucher\Exception\ShopDoesNotExist;
use App\Domain\API\Voucher\Exception\UsedVoucher;
use App\Domain\API\Voucher\Exception\VoucherDoesNotExist;
use App\Domain\API\Voucher\Exception\VoucherNotYetStarted;
use App\Domain\API\Voucher\Exception\VoucherPermission;
use App\Domain\API\Voucher\Exception\IncorrectCountry;
use App\Domain\API\Voucher\Exception\IncorrectCurrency;
use App\Domain\API\Voucher\Exception\NotEnoughPointsException;
use App\Domain\API\Voucher\Exception\NoVouchersAvailable;
use App\Domain\API\Voucher\InvalidatedVoucherResponse;
use App\Domain\API\Voucher\ReactivateRequest;
use App\Domain\Request;
use App\Exception\InvalidObjectException;
use App\Exception\UnauthorizedAccountException;

use App\Domain\API\Voucher\ReactivatedVoucherResponse;

use TFCLog\TFCLogger;
use bjsmasth\Salesforce\CRUD as SFClient;
use phpDocumentor\Reflection\Types\Boolean;
use DateTime;
use PVO\Validators\Url;
use Com\Tecnick\Barcode\Barcode;
use PhpParser\Node\Expr\Cast\Array_;

class RealVoucherProvider implements VoucherProvider
{
    private $adminpdo;
    private $tfcpdo;
    private $sfClient;
    private $fallback;

    protected $categories = [
	0 => 'all',
        1 => 'music',
        2 => 'games',
        3 => 'software',
        4 => 'mobile',
        5 => 'abooks',
        6 => 'ebooks',
        7 => 'movies',
        8 => 'emagazines',
        10 => 'giftcards',
        11 => 'newspapers',
        20 => 'dutyfree'
    ];

    protected $voucherCats = [
        0 => [
            0 => 'AllCategoriesVoucher'
        ],
        1 => [
            1 => 'MusicVoucher',
            2 => 'GameVoucher',
            3 => 'SoftwareVoucher',
            5 => 'AudioBookVoucher',
            6 => 'eBookVoucher',
            7 => 'MovieVoucher',
            8 => 'eMagVoucher',
            10 => 'GiftcardVoucher',
            11 => 'eNewspaperVoucher',
            20 => 'DutyFreeVoucher'
        ]
    ];

    protected $vcodeFormats = [
        'C128',
        'C128A',
        'C128B',
        'C128C',
        'C39',
        'C39+',
        'C39E',
        'C39E+',
        'C93',
        'CODABAR',
        'CODE11',
        'EAN13',
        'EAN2',
        'EAN5',
        'EAN8',
        'I25',
        'I25+',
        'IMB',
        'IMBPRE',
        'KIX',
        'LRAW',
        'MSI',
        'MSI+',
        'PHARMA',
        'PHARMA2T',
        'PLANET',
        'POSTNET',
        'RMS4CC',
        'S25',
        'S25+',
        'UPCA',
        'UPCE',
        'AZTEC',
        'DATAMATRIX',
        'PDF417',
        'QRCODE',
        'SRAW'
    ];

    public function __construct(PDO $adminpdo, PDO $tfcpdo, SFClient $sfClient)
    {
        $this->adminpdo = $adminpdo;
        $this->tfcpdo = $tfcpdo;
        $this->sfClient = $sfClient;
    }

    public function create(CreateRequest $request) : array
    {
        try {

            if($request->currency!=="" && $request->value>0) {
                $request->points = $this->convertToPoints($request->value, strtolower($request->currency), $request->shop_id);
            }

            if(isset($request->extra['app_order']) && $request->extra['app_order']) {
                $request->points = $request->extra['unit_points'];
            }

            $sql = "CALL CreateVouchers(:shopId, :accountId, :prefix, :num_vouchers, :points, :contentTypeId, :contentId, :voucherType, :userId, :startTime, :pointsStartTime, :expTime, :pointsExpTime)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":shopId", $request->shop_id, PDO::PARAM_STR);
            $stmt->bindValue(":accountId", $request->account_id, PDO::PARAM_STR);
            $stmt->bindValue(":prefix", $request->prefix, PDO::PARAM_STR);
            $stmt->bindValue(":num_vouchers", $request->num_vouchers, PDO::PARAM_STR);
            $stmt->bindValue(":points", $request->points, PDO::PARAM_STR);
            $stmt->bindValue(":contentTypeId", $request->content_type_id, PDO::PARAM_STR);
            $stmt->bindValue(":contentId", $request->content_id, PDO::PARAM_STR);
            $stmt->bindValue(":voucherType", $request->voucher_type, PDO::PARAM_STR);
            $stmt->bindValue(":userId", $request->user_id, PDO::PARAM_STR);
            $stmt->bindValue(":expTime", $request->exp_time, PDO::PARAM_STR);
            $stmt->bindValue(":startTime", $request->start_time, PDO::PARAM_STR);
            $stmt->bindValue(":pointsExpTime", $request->points_exp_time, PDO::PARAM_STR);
            $stmt->bindValue(":pointsStartTime", $request->points_start_time, PDO::PARAM_STR);
            $stmt->bindValue(":pointsStartTime", $request->points_start_time, PDO::PARAM_STR);
            $stmt->execute();
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new InvalidObjectException("CreateVouchers DB function failed");
            }

            if(count($request->extra)==0) {
                $request->extra = [];
            }
            
            $order = $this->createSFOrder($request, $request->account_id, $request->shop_id, $request->extra);
            $newVouchers = [];
            foreach ($res as $voucher) {

                $vchr = new Voucher($voucher);

                //add Voucher to Order
                $sfVoucher = $this->createSFVoucher($order, $request, $vchr);
                $newVouchers[] = $vchr->getData();

            }

            return $newVouchers;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }


    public function dfActivate(DFActivateRequest $request) 
    {
        $voucher['json'] = json_encode($request->data);
        $voucher['qr'] = "https://chart.googleapis.com/chart?cht=qr&choe=UTF-8&chs=400x400&chl=".urlencode($voucher['json']);
        $voucher['code'] = $request->voucher;

        $this->updateDFVoucher($voucher);

        return $this->getDFVoucherQr($request->voucher);
    }

    private function updateDFVoucher($voucher)
    {
        try {

            $sql = "CALL UpdateDFVoucher(:code, :qr, :json)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":code", $voucher['code'], PDO::PARAM_STR);
            $stmt->bindValue(":qr", $voucher['qr'], PDO::PARAM_STR);
            $stmt->bindValue(":json", $voucher['json'], PDO::PARAM_STR);
            $stmt->execute();

            //$res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            /*if (count($res) <= 0) {
                throw new InvalidObjectException("UpdateVouchers DB function failed");
            }*/

            //dd($res);

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }


    private function getDFVoucherQr($voucherCode)
    {
        try {

            $sql = "SELECT `qr`  FROM `vouchers` WHERE `code`=:voucherCode";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":voucherCode", $voucherCode, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new VoucherDoesNotExist();
            }

            foreach ($res as $voucher) {

                $vchr = [
                    "code" => $voucherCode,
                    "qr" => $voucher['qr']
                ];

                return $vchr;

            }

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function shopCreate(ShopCreateRequest $request) : array
    {
        try {

            $sku = explode('-', $request->sku);
            $expTime = "2099-12-31 00:00:00";
            $startTime = date("Y-m-d") . " 00:00:00";

            //update account taking into consideration company name and shop id
            $account = $this->updateAccount($request->company, intval($sku[0]));
            //update contact taking into consideration name, email and account id
            $client = $this->updateContact($request->first_name, $request->last_name, $request->email, $account['Id']);

            //get shop currency
            $shopCurrency = $this->getShopCurrency(intval($sku[0]));

            if(strtolower($shopCurrency)!==strtolower($request->currency)) {
                $request->value = $this->convertToShopCurrency($request->value, strtolower($request->currency), strtolower($shopCurrency));
                $request->currency = strtoupper($shopCurrency);
            }

            $points = $this->convertToPoints($request->value, strtolower($shopCurrency), intval($sku[0]));

            $sql = "CALL CreateShopVouchers(:shopId, :accountId, :num_vouchers, :points, :contentTypeId, :voucherType, :codes, :userId, :startTime, :pointsStartTime, :expTime, :pointsExpTime)";

            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":shopId", intval($sku[0]), PDO::PARAM_STR);
            $stmt->bindValue(":accountId", $account['AccountID__c'], PDO::PARAM_STR);
            $stmt->bindValue(":num_vouchers", $request->num_vouchers, PDO::PARAM_STR);
            $stmt->bindValue(":points", $points, PDO::PARAM_STR);
            $stmt->bindValue(":contentTypeId", intval($sku[1]), PDO::PARAM_STR);
            $stmt->bindValue(":voucherType", intval($sku[2]), PDO::PARAM_STR);
            $stmt->bindValue(":codes", $request->codes, PDO::PARAM_STR);
            $stmt->bindValue(":userId", 1, PDO::PARAM_STR);
            $stmt->bindValue(":expTime", $expTime, PDO::PARAM_STR);
            $stmt->bindValue(":startTime", $startTime, PDO::PARAM_STR);
            $stmt->bindValue(":pointsExpTime", $expTime, PDO::PARAM_STR);
            $stmt->bindValue(":pointsStartTime", $startTime, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new InvalidObjectException("CreateVouchers DB function failed");
            }

            if(intval($sku[2])===1) {
                $sku[1] = 0;
                $sku[2]--;
            }

            $order = $this->createSFOrder($request, $account['AccountID__c'], intval($sku[0]));

            $newVouchers = [];
            foreach ($res as $voucher) {
                $vchr = new Voucher($voucher);

                //add Voucher to Order
                $sfVoucher = $this->createSFShopVoucher($order, $request, $sku, $account, $points, $vchr);

                $newVouchers[] = $vchr->getData();
            }

            return $newVouchers;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }
    

    public function list(ListRequest $request) : array
    {
        try {

            //SELECT * FROM `vouchers` WHERE `session_id` = sessionId;

            $sql = "SELECT * FROM `vouchers`";
            $where = " WHERE 1=1";
            $searchVals = [];
            foreach ($request->where as $whereStatement) {
                $where .= " AND `".$whereStatement['field']."` = ?";
                $searchVals[] = $whereStatement['value'];
            }
            $sql .= $where;
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->execute($searchVals);

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new InvalidObjectException("No vouchers to list.");
            }

            $vouchers = [];
            foreach ($res as $voucher) {
                $vchr = new Voucher($voucher);
                $vouchers[] = $vchr->getData();
            }

            return $vouchers;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }


    public function listVoucherWallet(ListVoucherWalletRequest $request) : array 
    {
        try {
            
            $sql = "SELECT 
                `code`, 
                `points`, 
                `wallet`, 
                `content_type_id` as `contentType`, 
                `use_time` as `useTime`, 
                `shop_id` as `shopId`, 
                `points_start_time` as `pointsStartTime`, 
                `points_exp_time` as `pointsExpTime`, 
                `end_user_id` as `userId`, 
                `sfvoucher` as `sfId` 
                FROM `vouchers` 
                WHERE 
                `status`=3 AND end_user_id=:userId";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":userId", $request->end_user, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new NoVouchersAvailable();
            }

            return $res;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }

    }

    public function getVoucherWallet(VoucherWalletRequest $request) : array 
    {
        try {
            
            $sql = "SELECT 
                `code`, 
                `points`, 
                `wallet`, 
                `content_type_id` as `contentType`, 
                `use_time` as `useTime`, 
                `shop_id` as `shopId`, 
                `points_start_time` as `pointsStartTime`, 
                `points_exp_time` as `pointsExpTime`, 
                `end_user_id` as `userId`, 
                `sfvoucher` as `sfId` 
                FROM `vouchers` 
                WHERE 
                `status`=3 AND `code`=:code";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":code", $request->code, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new VoucherDoesNotExist();
            }

            return $res;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }

    }

    public function redeem(RedeemRequest $request) : RedeemedVoucher
    {
        try {
            $voucherDbResponse = $this->dbRedeem($request->code, $request->shop_id, $request->redeem);
            $errChck = $this->errorCheck($voucherDbResponse);
            if ($errChck) {
                throw $errChck;
            }
            $voucherDbResponse['userId'] = $request->end_user;
            $this->sfRedeem($voucherDbResponse, $request->end_user, $request->redeem);

            //get Salesforce Redeeemed Voucher
            $sfRedeemedVoucher = $this->getSFRedeemedVoucher($request->code, $request->end_user);

            //update voucher on DB with sfVoucherCode
            $voucherDBResponse = $this->dbUpdateSFVoucher($request->code, $request->shop_id, $request->end_user, $sfRedeemedVoucher['Id']);

            unset($voucherDbResponse['errCode']);
            
            return new RedeemedVoucher($voucherDbResponse);
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function redeemPointsFromVoucher(RedeemRequest $request) : RedeemedVoucher
    {
        try {

            //get voucher
            $voucher = $this->getVoucherByCode($request->code);

            if($request->redemption_value>$voucher['wallet']) {
                throw new NotEnoughPointsException();
            }

            $response = null;

            //check points against rules
            if($this->checkVoucherRules($voucher, $request->redemption_value)) {
                $response = $this->takePointsFromWallet($request, $voucher);
                $response['points'] = $request->redemption_value;
            } else {
                $response = $this->redeemVoucherIntoWallet($voucher);
            }

            $response = $this->convertResponseToRedeemedVoucher($response);

            return new RedeemedVoucher($response);

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }


    public function dfRedeem(DFRedeemRequest $request) : array
    {
        try {

            //get shop id
            $shopId = $this->getShopFromVoucher($request->voucherId);

            //ToDo: get user id TBD
            if($request->action=='redeem'){
                $voucherDbResponse = $this->dbRedeem($request->voucherId, $shopId, 1);
                
                $errChck = $this->errorCheck($voucherDbResponse);
                if ($errChck) {
                    throw $errChck;
                }
                //$voucherDbResponse['userId'] = $request->end_user;

                //ToDo: execute Salesforce Redemption

                unset($voucherDbResponse['errCode']);
                
                return [
                    "status" => "ok",
                    "message" => "Voucher with id " . $request->voucherId . " has been redeemed.",
                    "data" => $request->extra
                ];
            }

            if($request->action=='validate') {
                $voucherDbResponse = $this->dbRedeem($request->voucherId, $shopId, 0);
                
                $errChck = $this->errorCheck($voucherDbResponse);
                if ($errChck) {
                    throw $errChck;
                }

                unset($voucherDbResponse['errCode']);
                
                return [
                    "status" => "ok",
                    "message" => "Voucher with id " . $request->voucherId . " can been redeemed.",
                    "data" => $request->extra
                ];
            }
            
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }


    public function voucherExists(string $voucherCode)
    {
        try {

            $sql = "SELECT `code` FROM `vouchers` WHERE `code`=:voucherCode";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":voucherCode", $voucherCode, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new VoucherDoesNotExist();
            }

            return true;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function voucherIsActive(string $voucherCode)
    {
        try {

            $sql = "SELECT `code` FROM `vouchers` WHERE `code`=:voucherCode AND `status` IN (0,3)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":voucherCode", $voucherCode, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res)!=1) {
                throw new UsedVoucher();
            }
            
            return true;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function voucherNotExpired(string $voucherCode)
    {
        try {

            $sql = "SELECT `code` FROM `vouchers` WHERE `code`=:voucherCode AND `exp_time`>NOW()";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":voucherCode", $voucherCode, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new ExpiredVoucher();
            }
            
            return true;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function validCountryForVoucher(string $voucherCode, string $location)
    {
        try {

            $country = $this->getCountryFromLocation($location);

            $sql = "SELECT `code` FROM `vouchers` WHERE `code`=:voucherCode AND `json`->\"$.country\"=:country";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":voucherCode", $voucherCode, PDO::PARAM_STR);
            $stmt->bindValue(":country", $country, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new IncorrectCountry();
            }
            
            return true;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }


    public function validVoucherForLocation(string $voucherCode, string $location)
    {
        try{

            $locationSql = "SELECT `currency` FROM `dutyfree_locations` WHERE `IATA`=:location";
            $stmt = $this->tfcpdo->prepare($locationSql);
            $stmt->bindValue(":location", $location, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new IncorrectCountry();
            }
            
            foreach ($res as $r) {
                $curr = $r['currency'];
            }

            $sql = "SELECT `code` FROM `vouchers` WHERE `code`=:voucherCode AND `json`->\"$.currency\"=:currency";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":voucherCode", $voucherCode, PDO::PARAM_STR);
            $stmt->bindValue(":currency", $curr, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new IncorrectCountry();
            }
            
            return true;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }


    public function vCode(CodeRequest $request)
    {
        
        try {

            $format = $request->format;
            $code = $request->code;

            $vcode = $this->generateVcode($code, $format);

            return [
                "code" => $code,
                "image" => $vcode
            ];


        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }

    }

    public function imageCode($code, $format)
    {
        try {

            $vcode = $this->generateVcode($code, $format);

            return base64_decode($vcode);

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function getCountryFromLocation(string $location)
    {

        $sql = "SELECT `country_code` as `country` FROM `dutyfree_locations` WHERE `IATA`=:location";
            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":location", $location, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new IncorrectCountry();
            }
            
            foreach ($res as $r) {
                return $r['country'];
            }

    }

    public function validCurrencyForVoucher(string $voucherCode, string $currency)
    {
        try {

            $sql = "SELECT `code` FROM `vouchers` WHERE `code`=:voucherCode AND `json`->\"$.currency\"=:currency";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":voucherCode", $voucherCode, PDO::PARAM_STR);
            $stmt->bindValue(":currency", $currency, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new IncorrectCurrency();
            }
            
            return true;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }


    private function getShopFromVoucher(String $voucherCode) : int
    {
        $sql = "SELECT `shop_id` FROM `vouchers` WHERE `code`=:voucherCode";
        $stmt = $this->adminpdo->prepare($sql);
        $stmt->bindValue(":voucherCode", $voucherCode, PDO::PARAM_STR);
        $stmt->execute();

        $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (count($res) <= 0) {
            throw new InvalidObjectException("Voucher does not exist.");
        }

        foreach ($res as $r) {
            $shopId = $r['shop_id'];
        }

        return $shopId;

    }


    private function getVoucherByCode($code) : Array
    {
        $sql = "SELECT * FROM `vouchers` WHERE `code`=:code";
        $stmt = $this->adminpdo->prepare($sql);
        $stmt->bindValue(":code", $code, PDO::PARAM_STR);
        $stmt->execute();

        $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (count($res) <= 0) {
            throw new InvalidObjectException("Voucher does not exist.");
        }

        foreach ($res as $r) {
            $voucher = $r;
        }

        return $voucher;
    }


    private function checkVoucherRules($voucher, $value) : bool 
    {

        //get rules for voucher shop and category
        $rules = $this->getRules($voucher['shop_id'], $voucher['content_type_id']);

        //check if voucher below rule value
        $max = $voucher['points']*$rules['maxwallet'];
        if (($voucher['wallet']-$value)>($voucher['points']-$max)) {
            return true;
        }

        return false;

    }

    private function getRules($shopId, $catId) : Array 
    {

        $sql = "SELECT `maxwallet` FROM `voucher_rules` WHERE `shop` IN (0, :shopId) AND `category` IN (0, :catId)";
        $stmt = $this->adminpdo->prepare($sql);
        $stmt->bindValue(":shopId", $shopId, PDO::PARAM_STR);
        $stmt->bindValue(":catId", $catId, PDO::PARAM_STR);
        $stmt->execute();

        $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (count($res) <= 0) {
            throw new InvalidObjectException("Rule does not exist.");
        }

        foreach ($res as $r) {
            $rules = $r;
        }

        return $rules;

    }

    private function takePointsFromWallet($request, $voucher) : Array 
    {

        try {
            $wallet = $voucher['wallet'] - $request->redemption_value;
            $voucherCode = $voucher['code'];

            $sql = "UPDATE `vouchers` SET `wallet`=:wallet WHERE `code`=:code";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":wallet", $wallet, PDO::PARAM_STR);
            $stmt->bindValue(":code", $voucherCode, PDO::PARAM_STR);
            $stmt->execute();
            
            $voucher['wallet'] = $wallet;
            $voucher['points'] = $request->redemption_value;

            $this->updateSFVoucherWallet($voucher['sfvoucher'], $wallet);
                
            return $voucher;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }


    private function updateSFVoucherWallet($voucherId, $wallet) : bool 
    {
        try {
            $update = [
                'Wallet__c' => $wallet
            ];
            $sfvoucher = $this->sfClient->update('Voucher__c', $voucherId, $update);

            return true;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }

    }

    private function redeemVoucherintoWallet($voucher) : Array 
    {

        try {

            $voucher['points'] = $voucher['wallet'];
            $voucherCode = $voucher['code'];

            //update db voucher to used
            $sql = "UPDATE `vouchers` SET `status`=1, `wallet`=0 WHERE `code`=:code";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":code", $voucherCode, PDO::PARAM_STR);
            $stmt->execute();


            $voucher['status'] = 1;
            $voucher['wallet'] = 0;

            //update sf voucher to used
            $update = [
                'used__c' => true,
                'wallet__c' => 0
            ];

            $sfvoucher = $this->sfClient->update('Voucher__c', $voucher['sfvoucher'], $update);

            //get end user
            $endUserId = $voucher['end_user_id'];
            $endUser = $this->sfClient->query("Select Id, coins_count__c FROM TFC_User__c WHERE Id='" . $endUserId . "'");

            if ($endUser['totalSize'] !== 1) {
                throw new InvalidObjectException("No user found on Salesforce.");
            }
            $endUser = $endUser['records'][0];

            //update end user points
            $coins = $endUser['coins_count__c'] + $voucher['wallet'];
            $update = [
                'coins_count__c' => $coins
            ];
            $sfenduser = $this->sfClient->update('TFC_USER__c', $endUserId, $update);
            
            return $voucher;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }

    }


    private function convertResponseToRedeemedVoucher($response) : array 
    {

        $ret = [
            "code"              => $response["code"],
            "points"            => $response["points"],
            "contentType"       => $response["content_type_id"],
            "voucherType"       => $response["voucher_type"],
            "useTime"           => $response["use_time"],
            "shopId"            => $response["shop_id"],
            "pointsStartTime"   => $response["points_start_time"],
            "pointsExpTime"     => $response["points_exp_time"],
            "userId"            => $response["user_id"]
        ];

        return $ret;

    }


    private function createSFOrder($request, String $accountId, int $shopId, array $extra=[]) : string
    {
        try {

            $shopInfo = $this->getShopInfo($shopId);
            $currency = $shopInfo['CurrencyIsoCode'];

            //get Account Info
            $account = $this->sfClient->query("Select Id, Name, CurrencyIsoCode, AllowedShops__c FROM Account WHERE AccountID__c='" . $accountId . "'");

            if ($account['totalSize'] !== 1) {
                throw new InvalidObjectException("No account found on Salesforce.");
            }
            $account = $account['records'][0];

            //get Contract Info
            $sfContract = $this->sfClient->query("Select Id, CurrencyIsoCode FROM Contract WHERE AccountId='" . $account['Id'] . "' AND CurrencyIsoCode='" . $shopInfo['CurrencyIsoCode'] . "'");

            if(isset($extra['app_order']) && $extra['app_order']) {
                $shopInfo['CurrencyIsoCode'] = $request->currency;//$account['CurrencyIsoCode']; 
                $currency = $request->currency;
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
                    'Store__c'           => $shopInfo['Id'],
                    //'Status'            => 'Activated'
                ];
                $this->sfClient->create("Contract", $contract);
                $sfContract = $this->sfClient->query("Select Id, CurrencyIsoCode FROM Contract WHERE AccountId='" . $account['Id'] . "' AND CurrencyIsoCode='" . $shopInfo['CurrencyIsoCode'] . "' AND Store__c='" . $shopInfo['Id'] . "'");
            }
            
            $contract = $sfContract['records'][0];

            //get pricebook
            $pricebook = $this->sfClient->query("Select Id, Price_Book__c FROM PriceBooks_Stores__c WHERE Store__c='" . $shopInfo['Id'] . "'");

            if($pricebook['totalSize'] === 1){
                $pricebookId = $pricebook['records'][0]['Price_Book__c'];
            }

            if ($pricebook['totalSize'] !== 1) {
                $pricebook = $this->sfClient->query("Select Id FROM Pricebook2 WHERE Name='" . $currency . " Price Book'");
                $pricebookId = $pricebook['records'][0]['Id'];
            }


            $order = [
                'AccountId' => $account['Id'],
                'Pricebook2Id' => $pricebookId,
                'Status' => 'Draft',
                'ContractId' => $contract['Id'],
                'CurrencyIsoCode' => $shopInfo['CurrencyIsoCode'],
                'EffectiveDate' => date('Y-m-d'),
                'Description' => 'Voucher Order ' . date('d/m/Y'),
                'Shop__c' => $shopInfo['Id'],
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
                    'Unit_Points__c' => ((isset($extra['unit_points'])?$extra['unit_points']:"")),
                    'Contact_Name__c' => ((isset($extra['contact_id'])?$extra['contact_id']:"")),
                    'Order_Codes_Start_Date__c' => ((isset($extra['order_codes_start_time'])?$extra['order_codes_start_time']:"")),
                    'Order_Codes_Expire_Date__c' => ((isset($extra['order_codes_expire_time'])?$extra['order_codes_expire_time']:"")),
                    'Shop_Currency_Exchange_Rate__c' => ((isset($extra['shop_exchange_rate'])?$extra['shop_exchange_rate']:"")),
                    'Transaction_Exchange_Rate__c' => ((isset($extra['transaction_exchange_rate'])?$extra['transaction_exchange_rate']:"")),
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

    private function createSFVoucher(String $orderId, CreateRequest $request, Voucher $voucher) : string
    {
        try {

            //get currency
            $shop = $this->sfClient->query("Select ID, Name, CurrencyIsoCode, shop_name__c  FROM Client__c WHERE Name='" . $request->shop_id . "'");
            $shop = $shop['records'][0];
            $currency = $shop['CurrencyIsoCode'];
            $shopId = $shop['Name'];

            //get Account Info
            $account = $this->sfClient->query("Select Id, Name, CurrencyIsoCode, AllowedShops__c FROM Account WHERE AccountID__c='" . $request->account_id . "'");

            if ($account['totalSize'] !== 1) {
                throw new InvalidObjectException("Account does not exist on Salesforce");
            }

            $account = $account['records'][0];


            //get contact
            if (isset($request->extra['app_order']) && $request->extra['app_order']) {
                $contact = $this->sfClient->query("Select Id FROM Contact WHERE Email='" . $request->contact . "' AND AccountId='" . $account['Id'] . "'");
                $currency = $request->currency;//$account['CurrencyIsoCode'];  
                //check shop is part of allowed shops
                $allowedShops = explode(";", $account['AllowedShops__c']);
                if(!in_array($shop['shop_name__c'], $allowedShops)) {
                    throw new UnauthorizedAccountException("Account not authorized to create product");
                }
                $unitPrice = $request->extra['unit_price'];
            } else {
                $contact = $this->sfClient->query("Select Id FROM Contact WHERE LastName='API' AND AccountId='" . $account['Id'] . "'");
            }

            if($request->voucher_type==0) {
                $productName = $this->voucherCats[$request->voucher_type][0] . $currency;
            } else {
                $productName = $this->voucherCats[$request->voucher_type][$request->content_type_id] . $currency;
            }
            

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
            

            if ($contact['totalSize'] < 1) {
                throw new InvalidObjectException("Contact does not exist on Salesforce.");
            }

            $contact = $contact['records'][0];

            $quantity = 1;
            if($request->points > 0 && !isset($request->extra['unit_price'])) {
                //this will have to be modified to adjust to shop conversions
                $unitPrice = $request->points/100;
            }

            //create OrderItem
            $orderItem = [
                'Product2Id' => $product['Id'],
                'PricebookEntryId' => $pricebookEntry['Id'],
                'OrderId' => $orderId,
                'UnitPrice' => $unitPrice,
                'Quantity' => 1,
                'Account_Number__c' => $account['Id'],
                'Contact__c' => $contact['Id'],
                'Description' => "category: " . $this->getCategory($request->content_type_id) . " voucher code: " . $voucher->code,
                'ServiceDate' => date("Y-m-d", strtotime($voucher->start_time)),
                'EndDate' => date("Y-m-d", strtotime($voucher->exp_time))
            ];

            $orderItemId = $this->sfClient->create('OrderItem', $orderItem);

            return $orderItemId;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function createSFShopVoucher(String $orderId, ShopCreateRequest $request, Array $sku, Array $account, int $points, Voucher $voucher) : string
    {
        try {

            //get currency
            $shop = $this->sfClient->query("Select ID, Name, CurrencyIsoCode FROM Client__c WHERE Name='" . $sku[0] . "'");
            $shop = $shop['records'][0];
            $currency = $shop['CurrencyIsoCode'];
            $shopId = $shop['Name'];

            if($sku[2]==0) {
                $productName = $this->voucherCats[$sku[2]][0] . $currency;
            } else {
                $productName = $this->voucherCats[$sku[2]][$sku[1]] . $currency;
            }

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

            //get Account Info
            $account = $this->sfClient->query("Select Id, Name FROM Account WHERE AccountID__c='" . $account['AccountID__c'] . "'");

            if ($account['totalSize'] !== 1) {
                throw new InvalidObjectException("Account does not exist on Salesforce");
            }

            $account = $account['records'][0];

            //get contact
            $contact = $this->sfClient->query("Select Id FROM Contact WHERE LastName='API' AND AccountId='" . $account['Id'] . "'");

            if ($contact['totalSize'] < 1) {
                throw new InvalidObjectException("Contact does not exist on Salesforce.");
            }

            $contact = $contact['records'][0];

            $quantity = 1;
            if($points > 0) {
                $quantity = $points;
            }

            //create OrderItem
            $orderItem = [
                'Product2Id' => $product['Id'],
                'PricebookEntryId' => $pricebookEntry['Id'],
                'OrderId' => $orderId,
                'UnitPrice' => $pricebookEntry['UnitPrice'],
                'Quantity' => $quantity,
                'Account_Number__c' => $account['Id'],
                'Contact__c' => $contact['Id'],
                'Description' => "category: " . $this->getCategory($request->content_type_id) . " voucher code: " . $voucher->code,
                'ServiceDate' => date("Y-m-d", strtotime($voucher->start_time)),
                'EndDate' => date("Y-m-d", strtotime($voucher->exp_time))
            ];
            
            $orderItemId = $this->sfClient->create('OrderItem', $orderItem);

            return $orderItemId;

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

    private function getShopCurrency(int $shopId) : string 
    {
        try {
            $sql = "CALL GetCurrency(:shop)";
            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":shop", $shopId, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new InvalidObjectException("Shop doesn't exist.");
            }
            
            return $res['shop_currency'];
        } catch (\Exception $e) {
            return 'all';
        } catch (\Error $e) {
            return 'all';
        }
    }

    private function convertToShopCurrency(float $value, string $currency, string $shopCurrency): float 
    {
        try {

            $sql = "CALL ConvertBetweenCurrencies(:value, :currency, :shopCurrency)";
            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":value", $value, PDO::PARAM_STR);
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

            return (float)$conversion;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function convertToPoints(float $value, string $currency, int $shop) : float 
    {
        try {
            $sql = "CALL ConvertCurrency(:value, :currency, :shop)";
            $stmt = $this->tfcpdo->prepare($sql);
            $stmt->bindValue(":value", $value, PDO::PARAM_STR);
            $stmt->bindValue(":currency", $currency, PDO::PARAM_STR);
            $stmt->bindValue(":shop", $shop, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new InvalidObjectException("Currency not available.");
            }

            foreach ($res as $conv) {
                $conversion = $conv[$conv['choose']];
            }

            return $conversion;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }

    }

    private function dbRedeem(String $code, int $shopId, int $redeem) : array
    {
        try {
            $sql = "CALL RedeemVoucher(:code, :shopId, :redeem)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":code", $code, PDO::PARAM_STR);
            $stmt->bindValue(":shopId", $shopId, PDO::PARAM_STR);
            $stmt->bindValue(":redeem", $redeem, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new InvalidObjectException("RedeemVoucher DB function failed.");
            }
            return $res;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function errorCheck(array $voucherDbResponse)
    {
        switch ($voucherDbResponse['errCode']) {
            case 0:
                return false;
                break;
            case 1:
                return new VoucherDoesNotExist();
                break;
            case 2:
                return new IncorrectShop("Incorrect shop selected. Correct Shop: " . $voucherDbResponse['shopId']);
                break;
            case 3:
                return new VoucherNotYetStarted("Voucher has not yet started. Will be Available on " . $voucherDbResponse['pointsStartTime']);
                break;
            case 4:
                return new ExpiredVoucher("Voucher has expired on " . $voucherDbResponse['pointsExpTime']);
                break;
            case 5:
                return new UsedVoucher();
                break;
        }
    }

    private function sfRedeem(array $voucherdbResponse, String $end_user, int $redeem) : bool
    {
        try {

            //check voucher has not been redeemed on SF
            $sfvouchers = $this->sfClient->query("Select Id, Name FROM Voucher__c WHERE Name='" . $voucherdbResponse['code'] . "' AND used__c=true");

            if ($sfvouchers['totalSize']>0) {
                throw new UsedVoucher();
            }

            if($redeem>0) {

                $voucherdbResponse['pointsExpTime'] = date('Y-m-d', strtotime($voucherdbResponse['pointsExpTime']));

                $shop = $this->getShopInfo($voucherdbResponse['shopId']);

                if(!isset($voucherdbResponse['passId'])){
                    $voucherdbResponse['passId'] = "";
                }

                $pass = $this->getPassinfo($voucherdbResponse['passId']);

                $sfData = [
                    "Name"                      => $voucherdbResponse['code'],
                    "Purchased_clubcoins__c"    => $voucherdbResponse['points'],
                    "expire_date__c"            => $voucherdbResponse['pointsExpTime'],
                    "TFC_User__c"               => $voucherdbResponse['userId'],
                    "shop_name__c"              => $shop['shop_name__c'],
                    "shop_abbr__c"              => $shop['shop_abbr__c'],
                    "Category_ID__c"            => $this->getCategory($voucherdbResponse['contentType']),
                    "voucher_type__c"           => $voucherdbResponse['voucherType'],
                    "redeemed_pass__c"          => $pass['Id'],
                    "Wallet__c"                 => $voucherdbResponse['points'],
                    "used__c"                   => (($redeem==1)?true:false)
                ];
                //save redemption to SF
                return $this->sfClient->create("Voucher__c", $sfData);
            }

            return true;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function getSFRedeemedVoucher($voucherCode, $sfUser) : Array
    {
        $sfVoucher = $this->sfClient->query("Select Id FROM Voucher__c WHERE Name = '" . $voucherCode . "' AND TFC_User__c = '" . $sfUser . "'");

        if(!isset($sfVoucher['records'][0])) {
            throw new VoucherDoesNotExist();
        }

        return $sfVoucher['records'][0];
    }


    private function dbUpdateSFVoucher($code, $shopId, $user, $sfVoucher) : Array
    {
        try {
            $sql = "CALL UpdateSfVoucher(:code, :shopId, :user, :sfVoucher)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":code", $code, PDO::PARAM_STR);
            $stmt->bindValue(":shopId", $shopId, PDO::PARAM_STR);
            $stmt->bindValue(":user", $user, PDO::PARAM_STR);
            $stmt->bindValue(":sfVoucher", $sfVoucher, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new InvalidObjectException("UpdateSFVoucher DB function failed.");
            }
            return $res;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }



    private function updateAccount($company, $shopId) : Array
    {
        //get account

        $shop = $this->getShopInfo($shopId);

        $sfAccount = $this->sfClient->query("Select Id, Name, AccountID__c FROM Account WHERE Name='" . $company . "'");

        //if account does not exist
        if(!isset($sfAccount['records'][0])) {
            $acc = [
                'Name'              => $company,
                'Client_shop__c'    => $shop['Id'],
                'CurrencyIsoCode'   => $shop['CurrencyIsoCode']
            ];

            $this->sfClient->create("Account", $acc);
            $sfAccount = $this->sfClient->query("Select Id, Name, AccountID__c FROM Account WHERE Name='" . $company . "'");

        }

        $account = $sfAccount['records'][0];

        //check contract exists
        $sfContract = $this->sfClient->query("Select Id FROM Contract WHERE AccountId='" . $account['Id'] . "' AND CurrencyIsoCode='" . $shop['CurrencyIsoCode'] . "'");

        if(!isset($sfContract['records'][0])) {
            //create contract
            $contract = [
                'AccountId'         => $account['Id'],
                'CurrencyIsoCode'   => $shop['CurrencyIsoCode'],
                'StartDate'         => date('Y-m-d'),
                'ContractTerm'      => 12,
                //'Status'            => 'Activated'
            ];
            $this->sfClient->create("Contract", $contract);
            $sfContract = $this->sfClient->query("Select Id FROM Contract WHERE AccountId='" . $account['Id'] . "' AND CurrencyIsoCode='" . $shop['CurrencyIsoCode'] . "'");
        }


            //get client
            $sfContact = $this->sfClient->query("Select Id FROM Contact WHERE LastName='API' AND AccountId='" . $account['Id'] . "'");

            //if client does not exist
            if(!isset($sfContact['records'][0])) {
                $sfData = [
                    'AccountId' => $sfAccount['records'][0]['Id'],
                    'FirstName' => 'API',
                    'LastName'  => 'API'    
                ];
    
                $this->sfClient->create("Contact", $sfData);
            }

        //return account
        return $sfAccount['records'][0];

    }

    private function updateContact($firstName, $lastName, $email, $accountId) : Array 
    {
        //get client
        $sfContact = $this->sfClient->query("Select Id, Name FROM Contact WHERE Email='" . $email . "' AND AccountId='" . $accountId . "'");

        //if client does not exist
        if(!isset($sfContact['records'][0])) {
            $sfData = [
                'AccountId' => $accountId,
                'FirstName' => $firstName,
                'LastName'  => $lastName,
                //'Name'      => $firstName . ' ' . $lastName,
                'Email'     => $email       
            ];

            $this->sfClient->create("Contact", $sfData);
            $sfContact = $this->sfClient->query("Select Id, Name FROM Contact WHERE Email='" . $email . "' AND AccountId='" . $accountId . "'");
        }

        //return client
        return $sfContact['records'][0];

    }

    private function getShopInfo(int $shopId) : array
    {
        $sfShop = $this->sfClient->query("Select Id, shop_name__c, shop_abbr__c, CurrencyIsoCode FROM Client__c WHERE Name='" . $shopId . "'");
        return $sfShop['records'][0];
    }

    private function getPassInfo(string $passId) : array
    {
        $sfPass = $this->sfClient->query("Select Id FROM Redeemed_Pass__c WHERE Name='" . $passId . "'");
        if($sfPass['totalSize']>0) {
            return $sfPass['records'][0];
        }
        return ['Id' => ''];
    }

    public function invalidate(String $code) : Array
    {
        try {
            
            $sql = "CALL InvalidateVoucherById(:code)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":code", $code, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new VoucherDoesNotExist();
            }

            if($res['status']==1)
            {
                return [
                    "status" => "ok",
                    "message" => "voucher " . $code . " has been invalidated."
                ];
            }

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
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

    public function validVoucherFormat(Request $request) : bool
    {
        //ToDo: validate voucher format
        return true;
    }


    /**
     * Private methods
     */

    private function createFromRequest(CreateRequest $request) : Voucher
    {
        $voucherContent = json_decode((String)$request, true);

        $i=0;
        while ($i>$voucherContent['num_vouchers']) {
        }

        $voucherCode = $voucherContent["prefix"] . strtoupper(uniqid());

        return new Voucher(
            [
                "shop_id"           => "shop_id",
                "account_id"        => "account_id",
                "code"              => "code",
                "points"            => "points",
                "content_type_id"   => "content_type_id",
                "voucher_type"      => "voucher_type",
                "user_id"           => "user_id",
                "status"            => "status",
                "gen_time"          => "gen_time",
                "session_id"        => "session_id",
                "use_time"          => "use_time",
                "generator_id"      => "generator_id",
                "exp_time"          => "exp_time",
                "start_time"        => "start_time",
                "points_exp_time"   => "points_exp_time",
                "points_start_time" => "points_start_time",
                "api_user_id"       => "api_user_id",
                "timestamp"         => "timestamp",
                "signature"         => "signature"
            ]
        );
    }

    public function isValid(String $code) : Array
    {
        try {
            
            $sql = "CALL ValidateVoucherById(:code)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":code", $code, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new VoucherDoesNotExist();
            }

            if($res['status']==0)
            {
                return ['status' => 'ok', 'message' => "Voucher " . $code . " is valid."];
            }

            if($res['status']==1) {
                return ['status' => 'error', 'message' => "Voucher " . $code . " has been used."];
            }

            if($res['status']==2) {
                return ['status' => 'error', 'message' => "Voucher " . $code . " has been invalidated."];
            }
            

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }


    public function reactivate(String $code) : Array
    {
        try {
            
            $sql = "CALL ReactivateVoucher(:code)";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":code", $code, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            if (count($res) <= 0) {
                throw new VoucherDoesNotExist();
            }

            if($res['status']==0)
            {
                return [
                    "status" => "ok",
                    "message" => "voucher " . $code . " has been reactivated."
                ];
            }
            return false;

        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }


    public function visualFormatIsValid(string $format) : bool
    {
        try {
            return in_array($format, $this->vcodeFormats);
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    private function generateVcode(string $code, string $format) : string
    {
        try {
            
            // instantiate the barcode class
            $barcode = new Barcode();

            // generate a barcode
            $bobj = $barcode->getBarcodeObj(
                $format,                     // barcode type and additional comma-separated parameters
                $code,          // data string to encode
                ($code=="QRCODE")?150:350,                             // bar width (use absolute or negative value as multiplication factor)
                150,                             // bar height (use absolute or negative value as multiplication factor)
                'black',                        // foreground color
                array(5, 5, 5, 5)           // padding (use absolute or negative values as multiplication factors)
            )->setBackgroundColor('white'); // background color

            return base64_encode($bobj->getPngData());

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
     * @param VoucherProvider $fallback
     */
    public function attach(VoucherProvider $fallback)
    {
        $this->fallback = $fallback;
    }
}
