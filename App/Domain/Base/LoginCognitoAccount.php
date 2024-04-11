<?php

namespace App\Domain\Base;

use PVO\EmailAddress;
// PVO login does not exist yet
//use PVO\Login;

class LoginCognitoAccount
{

    private $email;
    private $status;

    public function __construct(
        EmailAddress $email, 
        array $status
    )
    {
        $this->email = $email;
        $this->status = $status;
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