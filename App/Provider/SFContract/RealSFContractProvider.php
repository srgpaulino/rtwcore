<?php

namespace App\Provider\SFContract;

use TFCLog\TFCLogger;
use bjsmasth\Salesforce\CRUD as SFClient;
use App\Exception\InvalidObjectException;
use App\Exception\InvalidLogException;
use Slim\Collection as Collection;

class RealSFContractProvider implements SFContractProvider
{

    private $logger;
    private $sfClient;
    private $settings;

    public function __construct(
        TFCLogger $logger,
        SFClient $sfClient,
        Collection $settings
    )
    {
        $this->logger = $logger;
        $this->sfClient = $sfClient;
        $this->settings = $settings;
    }

    /**
     * this will create User Contract Against user Account Id
     * @param array $ClientData
     * @return string
     * @throws \Exception
     */
    public function createSFContract(
        Array $ClientData
    ) : string
    {
        try {
//            dd($ClientData);
            return $this->sfClient->create(
                'Contract', 
                $ClientData
            );
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    /**
     * this will update status of Users Account Contract from Draft to Activated.
     * @param array $clientData
     * @return int
     * @throws \Exception
     */
    public function updateSFContractStatus(
        Array $clientData
    ) : int
    {
        try {
            $updateData = $clientData['contract'];

            return $this->sfClient->update(
                'Contract',
                $clientData['Id'], 
                $updateData
            );
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }
    
    /**
     * check if Account Id exist in Contract
     * @param type $accountId
     * @return type
     */
    public function getSFContract($accountId) {
        $sql = "SELECT "
            . "Id,"
            . "OwnerId,"
            . "AccountId,"
            . "CurrencyIsoCode,"
            . "Status,"
            . "StartDate,"
            . "ContractTerm,"
            . "General_notice_period__c"
            . " FROM Contract "
            . "WHERE (AccountId ='".$accountId."')";
            $result = $this->sfClient->query($sql);
            return $result;
    }
    
    public function getSettings(){
        return $this->settings;
    }

}