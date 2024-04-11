<?php

namespace App\Domain;

use PVO\Url;
use PVO\Phone;

class SalesforceAccount
{

    private $id;
    private $owner;
    private $name;
    private $type;
    private $phone;
    private $website;
    private $accountId;
    private $clientShop;
    private $marketSector;
    private $billingAddress;
    private $VAT;
    private $companyRegistration;
    private $sicDescription;

    public function __construct(
        string $id,
        string $owner,
        string $name,
        string $type,
        Phone $phone,
        Url $website,
        string $accountId,
        string $clientShop,
        string $marketSector,
        string $billingAddress,
        int $VAT,
        string $companyRegistration,
        string $sicDescription
    )
    {
        $this->id = $id;
        $this->owner = $owner;
        $this->name = $name;
        $this->type = $type;
        $this->phone = $phone;
        $this->website = $website;
        $this->accountId = $accountId;
        $this->clientShop = $clientShop;
        $this->marketSector = $marketSector;
        $this->billingAddress = $billingAddress;
        $this->VAT = $VAT;
        $this->companyRegistration = $companyRegistration;
        $this->sicDescription = $sicDescription;
    }

    public function __get($var)
    {
        if(property_exists($this, $var)) 
        {
            return $this->$var;
        }
        
        throw new ErrorException("Undefined property " . $var);
    }

    public function __set($var, $value) 
    {
        try{
            if(isset($this->$var))
            {   
                $this->$var = $value;
            }

            throw new ErrorException("Undefined property " . $var);
        } catch(\Exception $e) {
            return $e;
        } catch(\Error $e) {
            return $e;
        }        
    }

    public function __toString()
    {
        return json_encode($this);
    }

}