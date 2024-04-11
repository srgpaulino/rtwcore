<?php
namespace App\Provider\SFRedemption;

use TFCLog\TFCLogger;
use bjsmasth\Salesforce\CRUD as SFClient;
use App\Exception\InvalidObjectException;
use App\Exception\InvalidLogException;
use Slim\Collection as Collection;

class RealSFRedemptionProvider implements SFRedemptionProvider
{

    private $logger;

    private $sfClient;

    private $sfUserFields = [
        'Id' => 'Id',
        'coins_count__c' => 'coins_count__c',
        'name__c' => 'name__c',
        'geo_id__c' => 'geo_id__c',
        'address_1__c' => 'address_1__c',
        'address_2__c' => 'address_2__c',
        'city__c' => 'city__c',
        'client_id__c' => 'client_id__c',
        'date_of_birth__c' => 'date_of_birth__c',
        'email__c' => 'email__c',
        'last_name__c' => 'last_name__c',
        'phone__c' => 'phone__c',
        'language__c' => 'language__c',
        'mobile_phone__c' => 'mobile_phone__c',
        'login__c' => 'login__c',
        'additional_info_1__c' => 'additional_info_1__c',
        'additional_info_2__c' => 'additional_info_2__c',
        'external_user_id__c' => 'external_user_id__c'
    ];

    public function __construct (TFCLogger $logger, SFClient $sfClient)
    {
        $this->logger = $logger;
        $this->sfClient = $sfClient;
    }

    public function createSFRedemption (array $data) : string 
    {
        try {
            //create redemption
            $redemptionId = $this->sfClient->create(
                'Download_history__c',
                $data
            );
            return $redemptionId;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }


    public function getSFRedemption (string $orderId): array {

        $result = [];

        $sql = "SELECT "
            . "Id,"
            . "content_id__c,"
            . "order_id__c,"
            . "download_date__c,"
            . "product_image__c,"
            . "content_title__c,"
            . "Label_publisher_name__c,"
            . "Feed_provider__c,"
            . "Category_ID__c,"
            . "Content_type__c,"
            . "Transaction_Status__c,"
            . "TFC_User__c,"
            . "username__c,"
            . "Name__c,"
            . "Email__c,"
            . "User_territory__c,"
            . "Store_Id__c,"
            . "Shop_abbr__c,"
            . "available_clubcoins__c,"
            . "Client_User_ID__c,"
            . "Client_Shop_Name__c,"
            . "currency__c,"
            . "Cost__c,"
            . "purchasing_price__c,"
            . "transaction_price__c,"
            . "Transaction_Fee__c,"
            . "Product_Discount__c,"
            . "Product_Fee__c,"
            . "Clubcoins__c,"
            . "Transaction_Exchange_Rate__c,"
            . "VAT__c,"
            . "Provider_Margin__c,"
            . "Provider_Discount__c,"
            . "Special_Offer__c,"
            . "Discount__c,"
            . "Offer_SRP__c,"
            . "Offer_Id__c,"
            . "Offer_Name__c,"
            . "Provider_order_number__c,"
            . "download_url__c,"
            . "Additional_info__c"
            . " FROM Download_history__c "
            . "WHERE (order_id__c ='".$orderId."')";
        ;

        $result = $this->sfClient->query($sql);
        foreach($result['records'] as $res ) {
            return $res;
        }

    }


    public function updateUserPoints($user) 
    {
        ddd("user: ");
        ddd($user);

        //get User
        $userId = $user['RTWUserId'];
        $userData = $this->getUser($userId);

        //change available points
        $userData['coins_count__c'] = $user['coinsCount'];
        unset($userData['Id']);

        ddd("userId: " . $userId);
        ddd("userData: ");
        ddd($userData);

        //update user
        $this->sfClient->update('TFC_User__c', $userId, $userData);
        return $userData;

    }


    private function getUser($userId) 
    {
        $result = [];

        $sql = "SELECT " . implode(',', $this->sfUserFields) . " FROM TFC_User__c WHERE Id='" . $userId . "'";

        $result = $this->sfClient->query($sql);
        foreach($result['records'] as $res ) {
            return $res;
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