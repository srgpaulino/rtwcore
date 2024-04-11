<?php

namespace App\Provider\SFAccount;

use bjsmasth\Salesforce\CRUD as SFClient;
use TFCLog\TFCLogger;
use App\Exception\InvalidObjectException;
use App\Exception\InvalidLogException;
use App\Domain\SalesforceAccount;

class RealSFAccountProvider implements SFAccountProvider
{

    private $logger;
    private $sfClient;
    private $fallback;
    //salesforce account fields
    private $sfAccountFields = [
        'single' => [
            'id'                    => 'Id',
            'owner'                 => 'OwnerId',
            'name'                  => 'Name',
            'type'                  => 'Type',
            'phone'                 => 'Phone',
            'website'               => 'Website',
            'accountId'             => 'AccountID__c',
            'clientShop'            => 'Client_shop__c',
            'marketSector'          => 'Market_Sector__c',
            'billingAddress'        => 'BillingAddress',
            'VAT'                   => 'VAT__c',
            'companyRegistration'   => 'Company_Registration__c',
            'sicDescription'        => 'SicDesc'
        ],
        'collection' => [
            'sfAccountId'           => 'Id',
            'name'                  => 'Name',
            'accountId'             => 'AccountID__c',
        ]
    ];
    const CLEARDATA = [
        'Name'                      => 'Anonymous',
        'BillingStreet'             => '',
        'BillingCity'               => '',
        'BillingState'              => '',
        'BillingPostalCode'         => '',
        'BillingCountry'            => '',
        'Phone'                     => '',
        'Fax'                       => '',
        'Website'                   => '',
        'IBAN__c'                   => '',
        'VAT__c'                    => '',
        'Company_Registration__c'   => ''
    ];

    public function __construct(
        TFCLogger $logger,
        SFClient $sfClient
    )
    {
        $this->logger = $logger;
        $this->sfClient = $sfClient;   
    }

    public function getSFAccount(
        string $search, 
        string $mode='single'
    ) : Array 
    {
        try {
            $sql = "SELECT " . 
                implode(',', $this->sfAccountFields[$mode]) . 
                " FROM Account WHERE Name='" . 
                $search . 
                "' ";
            $sql .= 'single' == $mode ? ' LIMIT 1' : '';
            $result = $this->sfClient->query($sql);
//            dd($sql);
            $accounts = [];
            foreach($result['records'] as $res ) {
                $account = [];
                foreach($this->sfAccountFields[$mode] as $key => $value) {
                    $account[$key] = $res[$value];
                }
                $accounts[] = $account;
            }
            if(count($accounts) === 1) {
                return $accounts[0];
            }
            elseif(count($accounts) == 0){
                throw new \App\Domain\TFCBlue\Authentication\Exception\SFNotFound('SalesForce account not found for "'.$search.'"');
            }
            return $accounts;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function createSFAccount(
        Array $data
    ) : string
    {
        try {
            return $this->sfClient->create(
                'Account', 
                $data
            );
        }
        catch (\Exception $e) {
            if($this->isDuplicateException($e)){
               throw new \App\Domain\TFCBlue\Authentication\Exception\SFAlreadyExists('SF account for "'.$data['Name'].'" already exists.');
            }else{
                throw $e;
            }
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function updateSFAccount(
        Array $data
    ) : SalesforceAccount
    {
        try {
            $accountData = $data['account'];
            return $this->sfClient->update(
                'Account',
                $data['sfaccountid'], 
                $accountData
            );
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function sfContactsPerAccount(
        Array $data
    ): int
    {
        try {
            //ToDo
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function anonimizeSFAccount(
        Array $data
    ) : SalesforceAccount
    {
        try {
            return $this->sfclient->update(
                'Account',
                $data['sfaccountid'], 
                self::CLEARDATA
            );
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
     * @param SFAccountProvider $fallback
     */
    public function attach(SFAccountProvider $fallback)
    {
        $this->fallback = $fallback;
    }

    /**
     * Check if this message is about duplicate record
     * Note that message can change. We don't get proper error code here
     * @param Exception $e
     * @return bool
     */
    private function isDuplicateException(\Exception $e): bool {
        $message = $e->getMessage();
        if ($e->getCode() == 400 && stripos($message, 'creating a duplicate record') !== false) {
            return true;
        }
        return false;
    }

}