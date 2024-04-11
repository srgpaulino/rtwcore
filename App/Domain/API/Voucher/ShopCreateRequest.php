<?php

namespace App\Domain\API\Voucher;

use App\Domain\Request;
use DateTime;
use App\Domain\Currency;

class ShopCreateRequest extends Request
{
    protected $structure = [
        "first_name"            => "string",
        "last_name"             => "string",
        "company"               => "string",
        "email"                 => "email",
        "num_vouchers"          => "intlist",
        "currency"              => "currency",
        "value"                 => "floatlist",
        "codes"                 => "csa",
        "sku"                   => "skulist"
    ];

    public function getMultiRequest() : Array
    {
        $ret = [];
        $i = 0;
        $max = 99999;
        do {
            $req = $this->content;
            foreach ($this->structure as $field => $value) {
                //separate lists
                if (strpos($value, 'list') !== false) {
                    $aux = explode(',', $this->content[$field]);
                    $max = count($aux);
                    if(isset($aux[$i])){
                        $req[$field] = $aux[$i];
                    }
                }
                //separate codes if list applies
                if ($value === 'csa') {
                    $aux = explode(',', $this->content[$field]);
                    $codes = $this->getVoucherCodes($i, $this->content['num_vouchers'], $aux);
                    $req[$field] = implode(',', $codes);
                }
            } 
            $ret[] = $req;
            $i++;
        }while($i<$max);
        return $ret;
    }

    protected function getVoucherCodes($position, $numVouchers, $codes) : array
    {
        $aux = explode(',', $numVouchers);
        $quantity = 0;
        for($i=0; $i<$position;$i++) {
            $quantity += $aux[$i];
        }
        return array_slice($codes, $quantity, $aux[$i]);
    }

    protected function validatePrefix($prefix) : bool
    {
        return (
            (strlen($this->content['prefix']) >= 2) &&
            (strlen($this->content['prefix']) <= 4)
        );
    }

    protected function validateDateTime($date) : bool
    {
        return  (
            (date('Y-m-d H:i:s', strtotime($date)) !== false)
        );
    }

    protected function validateEndDateTime($endDateTime) : bool
    {
        return  (
            (date('Y-m-d H:i:s', strtotime($endDateTime)) !== false) &&
            (date('Y-m-d H:i:s', strtotime($this->content['start_time'])) <= date('Y-m-d H:i:s', strtotime($endDateTime)))
        );
    }

    protected function validateEmail($email) : bool
    {
        return (
            preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $this->content['email'])
        );
    }

    protected function validateCurrency($currency) : bool
    {
        return (
            Currency::currencyExists($this->content['currency'])
        );
    }

    protected function validateIntlist($values) : bool 
    {
        if( count( explode( ',', $this->content['codes'] ) ) <= 0 ) {
            return false;
        }

        $quantities = explode(',', $this->content['num_vouchers']);

        foreach($quantities as $quantity) {
            if(((string)(int)$quantity != $quantity)){
                return false;
            }
        }

        return true;
    }

    protected function validateFloatlist($values) : bool 
    {
        if( count( explode( ',', $this->content['value'] ) ) <= 0 ) {
            return false;
        }

        $quantities = explode(',', $this->content['value']);

        foreach($quantities as $quantity) {
            $floatVal = floatval($quantity);
            if(!$floatVal ){
                return false;
            }
        }

        return true;
    }

    protected function validateCsa($csa) : bool 
    {
        return (
            ( count( explode( ',', $this->content['codes'] ) ) > 0 )
        );
    }

    protected function validateSkulist($sku) : bool 
    {
        $skus = explode(',', $this->content['sku']);

        foreach($skus as $skuValue){
            $skuArray = explode( '-', $skuValue );

            if(count($skuArray)!=3) {
                return false;
            }

            foreach($skuArray as $id) {
                if(!is_numeric($id)) {
                    return false;
                }
            }
        }
        

        return true;
    }
}
