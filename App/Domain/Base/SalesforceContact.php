<?php

namespace App\Domain;

use PVO\EmailAddress;
use PVO\Phone;
use App\Domain\SalesforceAccount;
use phpDocumentor\Reflection\Types\Boolean;


class SalesforceContact
{

    private $id;
    private $owner;
    private $account;
    private $firstName;
    private $lastName;
    private $email;
    private $phone;
    private $title;
    private $currency;
    private $country;
    private $mailingAddress;
    private $shop;
    private $emailOptOut;
    private $clientOf;

    public function __construct(
        string $id,
        string $owner,
        SalesforceAccount $account,
        string $firstName,
        string $lastName,
        EmailAddress $email,
        Phone $phone,
        string $title,
        string $currency,
        string $country,
        string $mailingAddress,
        string $shop,
        bool $emailOptOut,
        string $clientOf
    )
    {
        $this->id = $id;
        $this->owner = $owner;
        $this->account = $account;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phone = $phone;
        $this->title = $title;
        $this->currency = $currency;
        $this->country = $country;
        $this->mailingAddress = $mailingAddress;
        $this->shop = $shop;
        $this->emailOptOut = $emailOptOut;
        $this->clientOf = $clientOf;
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
