<?php

namespace App\Helper\Providers;

use PDO;

use TFCLog\TFCLogger;
use App\Repository\Logger\DBLogger;
use bjsmasth\Salesforce\CRUD as SFClient;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\String_;

use Exception;
use App\Exception\CoreExceptionCritical;

class Provider {

    /**
     * Provider name
     *
     * @var string
     */
    protected $_provider = '';

    /**
     * Auth data
     *
     * @var array
     */
    protected $_auth = [];

    /**
     * Environment
     *
     * @var string
     */
    protected $_environment = 'production';

    /**
     * Provider data
     *
     * @var array
     */
    protected $_data = [];

    /**
     * All provider data by content
     *
     * @var array
     */
    protected $_dataByContent = [];

    /**
     * Provider contend can by redownloaded
     *
     * @var array
     */
    protected $_redownload = [];

    /**
     * Provider has history
     *
     * @var array
     */
    protected $_history = [];

    protected $_shop = [];

    protected $tfcpdo;
    protected $sfClient;
    protected $logger;

    protected $mrgs = [];


    public function __construct(PDO $tfcpdo, SFClient $sfClient, $auth)
    {
        $this->tfcpdo = $tfcpdo;
        $this->sfClient = $sfClient;   

        $this->_auth = $auth;

        //fetch margins values
        $sql = "SELECT tax, margin, discount
        FROM margins
        WHERE provider = :provider";
        $stmt = $this->tfcpdo->prepare($sql);
        $stmt->bindValue(":provider", $this->_provider, PDO::PARAM_STR);
        $stmt->execute();

        $this->mrgs = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function setLogger($logId, $order, $shopId) {
        $this->logger = new DBLogger($this->tfcpdo);
        $this->logger->setShop($shopId);
        $this->logger->startOrder($order);
        $this->logger->setLogId($logId);
    }


    /**
     * Retrieve auth data
     *
     * @param string $key
     * @return mixed
     */
    public function getAuth($key = null)
    {
        $data = $this->_data;
        if(isset($data['auth'][strtolower($this->_provider)][$this->_environment])) {
            $this->_auth[$this->_environment] = $data['auth'][strtolower($this->_provider)][$this->_environment];
        }

        if(null === $key) {
            return $this->_auth[$this->_environment];
        }
        return array_key_exists($key, $this->_auth[$this->_environment]) ? $this->_auth[$this->_environment][$key] : null;
    }

    public function setShop($shop) {
        $this->_shop = $shop;
        $this->logger = new DBLogger($this->tfcpdo, $shop);
    }

    /**
     * Provider data setter
     *
     * @param array $data
     * @return Module_Providers_Model_Abstract
     */
    public function setData(array $data)
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * Provider data getter
     *
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Set provider data by content
     *
     * @return Module_Providers_Model_Abstract
     */
    public function setDataByContent(array $data)
    {
        $this->_dataByContent = $data;
        return $this;
    }

    /**
     * Retrieve data by content
     *
     * @return mixed
     */
    public function getDataByContent($content, $field = null)
    {
        $content = strtolower($content);
        if(isset($this->_dataByContent[$content])) {
            if(null === $field) {
                return $this->_dataByContent[$content];
            }
            return isset($this->_dataByContent[$content][$field]) ? $this->_dataByContent[$content][$field] : null;
        }
        return null;
    }

    /**
     * Provider content can by redownloaded or not
     *
     * @param string $content
     * @param array $data
     * @return bool
     */
    public function hasRedownload($content, $data = [])
    {
        return !empty($this->_redownload[$content]);
    }

    /**
     * Provider has history
     *
     * @param string $content
     * @return bool
     */
    public function hasHistory($content)
    {
        return !empty($this->_history[$content]);
    }

    /**
     * Download dates getter
     *
     * @param string $content
     * @param array $sfHistory
     * @return array
     */
    public function getDownloadDates($content, $sfHistory)
    {
        $dates = [];

        foreach($sfHistory as $item) {
            if($content == $item['content_type']) {
                $dates[$item['provider'] . '-' . $item['content_id']] = $item['date'];
            }
        }
        return $dates;
    }

    /**
     * Provider id getter
     *
     * @param string $contentType Content type
     * @return int
     */
    public function getId($contentType = null)
    {
        if(count($this->_data) > 1) {
            if(!empty($contentType)) {
                foreach($this->_data as $id => $type) {
                    if(strtolower($type) == strtolower($contentType)) {
                        return $id;
                    }
                }
            } else {
                throw new CoreExceptionCritical('Please provide content type');
            }
        }
        return key($this->_data);
    }

    public function getSalesforce()
    {
        return $this->sfClient;
    }

    /**
     * Adds download history to salesforse
     *
     * @param array $data
     * @return mixed
     */
    public function addDownloadHistory($data)
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

        $platformInfo = [
            'compactName' => 'API'
        ];

        $userEmail = '';
        if(!empty($data['user_email'])) {
            $userEmail = $data['user_email'];
        }

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
            'Shop_abbr__c'              => substr(strtoupper(self::$SHOP['abbreviation']), 0, 4),
            'username__c'               => substr($this->auth->getLogged('login__c'), 0, 50),
            'User_territory__c'         => substr($this->auth->getLogged('geo_id__c'), 0, 5),
            'download_end_date__c'      => $data['download_end_date'],
            'Additional_info__c'        => substr($data['additional_info'], 0, 32768),
            'download_url__c'           => substr($data['download_url'], 0, 1500),
            'content_voucher__c'        => substr($data['external_url'], 0, 1500),
            'voucher_code__c'           => substr($data['voucher_code'], 0, 250),
            'VOD_SD__c'                 =>$data['sd_voucher'],
            'VOD_HD__c'                 =>$data['hd_voucher'],
            'VOD_Premium__c'            =>$data['premium_voucher'],
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
            $result = $this->getSalesforce()->insert($fieldset, 'Download_history__c');
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

    /**
     * Has the item been redeemed before?
     * @param array $daya
     * @return boolean
     */
    public function hasRedeemedBefore($data)
    {
        if(isset($data['track'])){
            $data['provider_product_id'] = $data['track']['provider_track_id'];
        }
        elseif(isset($data['provider_album_id'])){
            $data['provider_product_id'] = $data['provider_album_id'];
        }

        $query = "Select Id FROM Download_history__c WHERE content_id__c=".$data['provider_product_id'].".0 AND TFC_User__c='".(string)$this->auth->getLogged('Id')."' AND Feed_provider__c='".(string)$this->_provider."' AND Re_Redeem__c=false";
        $result = $this->DAO('model', 'content')->getSalesforce()->get($query);
        if($result->size > 0){
            return true;
        }
        return false;
    }

    /**
     * Adds redeem download to salesforse
     *
     * @param array $data
     * @return void
     */
    public function addRedeemDownload($data)
    {
        // Fields check
        foreach(array('isrc' => '', 'artist' => '', 'publisher' => '', 'remaining_downloads' => '0', 'additional_info' => '') as $field => $value) {
            if(empty($data[$field])) {
                $data[$field] = $value;
            }
        }

        $fieldset = array(
            'Category_ID__c'         => $data['content_type'],
            'Content_ID__c'          => $data['provider_product_id'],
            'Content_title__c'       => $data['title'],
            'isrc__c'                => $data['isrc'],
            'artist__c'              => $data['artist'],
            'Remaining_downloads__c' => $data['remaining_downloads'],
            'TFC_User__c'            => $this->auth->getLogged('Id'),
            'Feed_provider__c'       => $this->_provider,
            'Shop_abbr__c'           => strtoupper(self::$SHOP['abbreviation']),
            'Additional_info__c'     => $data['additional_info']
        );

        foreach($fieldset as $k => $v) {
            $fieldset[$k] = htmlspecialchars($v);
        }

        $this->getSalesforce()->insert($fieldset, 'Redeems_history__c');
    }

	public function getProviderSampleUrl($id = false)
	{
		return null;
	}

    public function getSerialNumbers($content, $data)
    {
        return null;
    }

    public function getDownloadManagerHtml($data = [])
    {
        return null;
    }

    public function getDownloadLogs($providerProductId){
        if (empty($providerProductId)){
            return false;
        }

        $user = $this->auth->getLogged();

        $sql = "SELECT id,
                       created_at,
                       message
                  FROM mc_logs
                 WHERE shop_id = ?
                   AND provider = ?
                   AND content_name = 'music'
                   AND user_id = ?
                   AND product_id = ?
              ORDER BY id DESC";

        $result = DB::fetchAll($sql, array(self::$SHOP['id'], $this->_provider, $user['Id'], $providerProductId));

        return $result;
    }

    public function getDownloadLog($logId){
        return DB::fetchRow('SELECT provider, message, product_id FROM mc_logs WHERE id = :id', array('id' => $logId));
    }

    /**
     * Get Content Meta Data
     *
     * @param INT $providerId
     * @param INT $contentType
     * @param INT $contentId
     * @return ARRAY
     */
    public function getMeta($providerId, $contentType, $contentId){
        if (is_int($providerId) && is_int($contentType) && is_int($contentId)){
            $sql = "SELECT meta_key, meta_value
                      FROM metadata
                     WHERE provider_id = ?
                       AND content_type = ?
                       AND content_id = ?";
            $data = [];
            foreach (DB::fetchAll($sql, array($providerId, $contentType, $contentId)) AS $key=>$value){
                $data[$value['meta_key']] = $value['meta_value'];
            }
            return $data;
        }
        return [];
    }

    public function checkDownload($userId, $expires, $count){
        return $this->DAO('model', 'download')->checkDownload($userId, $expires, $count);
    }

    /**
     * Set data during ajax download
     *
     * @param object $tpl Template onject to set data to
     * @param array $data
     */
    public function download($tpl, $data){}

    /**
     * Set data during ajax download success
     *
     * @param object $tpl Template onject to set data to
     * @param array $data
     */
    public function downloadSuccess($tpl, $data){}

    public function logCommonData($content, $data){
        $this->DAO('model', 'logs')
             ->addUserId($this->auth->getLogged('Id'))
             ->addUserName($this->auth->getLogged('login__c'))
             ->addProductId($data['provider_product_id'])
             ->addProductName($data['title'])
             ->addProductPrice(serialize($data['price']))
             ->addProvider($this->_provider)
             ->addOrderId($data['order_id'])
             ->logIt();
    }

    private function getImage($data, $contentType, $model) {

        if ($contentType == 'Audio Books') $contentType = 'abooks';

        if(isset($data['album'])) {
            $data = $data['album'];
        }

        if(!empty($data['image'])) {
            if (isset($data['provider_album_id'])) {
                $dataId = $data['provider_album_id'];
            } elseif (isset($data['current_issue_id'])) {
                $dataId = $data['provider_product_id']."_".$data['current_issue_id'];
            } else {
                $dataId = $data['provider_product_id'];
            }

            $image = 'pi-medium-' . $data['provider_id'] . '/' . $data['image'] . '/' . $dataId;
        } else {
            $image = 'pi-blank';
        }

        $segments = explode('-', $image);

        return PROTOCOL ."://" . App_Config::CDN_DOMAIN . "/{$segments[2]}_{$segments[1]}.jpg";
    }
    
}