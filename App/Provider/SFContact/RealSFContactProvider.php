<?php

namespace App\Provider\SFContact;

use TFCLog\TFCLogger;
use bjsmasth\Salesforce\CRUD as SFClient;
use App\Exception\InvalidObjectException;
use App\Exception\InvalidLogException;
use App\Provider\SFContact\SFContactProvider;

class RealSFContactProvider implements SFContactProvider
{

    private $logger;
    private $sfClient;
    private $fallback;
    //salesforce contact fields
    private $sfContactFields = [
        'single' => [
            'id'                => 'Id',
            'owner'             => 'OwnerId',
//            'account'           => 'Account',
            'firstName'         => 'FirstName',
            'lastName'          => 'LastName',
            'email'             => 'Email',
            'phone'             => 'Phone',
            'title'             => 'Title',
            'currency'          => 'CurrencyIsoCode',
            'country'           => 'MailingCountry',
            'mailingAddress'    => 'MailingAddress',
            'shop'              => 'External_Shop_Id__c',
            'emailOptOut'       => 'HasOptedOutOfEmail',
            'clientOf'          => 'Client_Of__c'
        ],
        'collection' => [
            'sfAccountId'   => 'Id',
            'firstName'     => 'FirstName',
            'lastName'      => 'LastName',
            'country'       => 'MailingCountry'
        ]
    ];
    const CLEARDATA = [
        'LastName'          => 'Ymous',
        'FirstName'         => 'Anon',
        'Name'              => 'Anonymous',
        'MailingStreet'     => '',
        'MailingCity'       => '',
        'MailingState'      => '',
        'MailingPostalCode' => '',
        'MailingCountry'    => '',
        'Phone'             => '',
        'Fax'               => '',
        'MobilePhone'       => '',
        'Email'             => 'anonymous@thefirstclub.com'
    ];

    public function __construct(
        TFCLogger $logger,
        SFClient $sfClient
    )
    {
        $this->logger = $logger;
        $this->sfClient = $sfClient;
    }

    public function getSFContact(
        string $search,
        string $mode = 'single'
    ): Array {
        try {
            $sql = "SELECT " .
                implode(',', $this->sfContactFields[$mode]) .
                " FROM Contact WHERE (Email ='" .
                $search .
                "')";
            
            $sql .= 'single' == $mode ? ' LIMIT 1' : '';

            $result = $this->sfClient->query($sql);
            $contacts = [];
            foreach ($result['records'] as $res) {
                $contact = [];
                foreach ($this->sfContactFields[$mode] as $key => $value) {
                    $contact[$key] = $res[$value];
                }
                $contacts[] = $contact;
            }
            if (count($contacts) === 1) {
                return $contacts[0];
            }
            return $contacts;
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function createSFContact(
        Array $ClientData
    ) : string
    {
        try {
//            dd($ClientData);
            return $this->sfClient->create(
                'Contact', 
                $ClientData
            );
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function updateSFContact(
        Array $clientData
    ) : Array
    {
        try {
            $contactData = $clientData['contact'];
            return $this->sfClient->update(
                'Contact',
                $clientData['sfcontactid'], 
                $contactData
            );
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }

    public function anonimizeSFContact(
        Array $clientData
    ) : Array
    {
        try {
            
            return $this->sfClient->update(
                'Contact',
                $clientData['sfcontactid'], 
                self::CLEARDATA
            );            
        } catch (\Exception $e) {
            throw $e;
        } catch (\Error $e) {
            throw $e;
        }
    }
    
    public function exists(
        string $search
    ): bool {
        $contact = $this->getSFContact($search);
        return count($contact) > 0 ? true : false;
    }

    /**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param SFContactProvider $fallback
     */
    public function attach(
        SFContactProvider $fallback
    )
    {
        $this->fallback = $fallback;
    }

}