<?php

use bjsmasth\Salesforce\CRUD as SFClient;

//print to screen
function d($var)
{
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}

//print to screen and exit
function dt($var)
{
    d($var);
    die();
}

//print to log file
function ddd($var)
{
    syslog(LOG_INFO, json_encode($var));
}

//var_dump and exit
function vd($var)
{
    var_dump($var);
    die();
}

// Modified from Laravel env function
function env($key, $default = null)
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    if (substr($value, 0, 1) == '"' && substr($value, -1) == '"') {
        return substr($value, 1, -1);
    }

    return envVal($value);
}

//convert string values to their counterparts
function envVal($value)
{
    if (strpos($value, 'true')) {
        return true;
    }

    if (strpos($value, 'false')) {
        return false;
    }

    if (strpos($value, 'empty')) {
        return '';
    }

    if (strpos($value, 'null')) {
        return null;
    }

    return $value;
}

function Encrypt($subject, $method, $saltLength, $key)
{
    $salt=random_bytes($saltLength);
    $key=hash('sha256', $salt.$key);
    $iv=random_bytes(16);
    $encrypt=base64_encode(openssl_encrypt($subject, $method, $key, 0, $iv));
    $return=base64_encode(json_encode(["method"=>$method, "salt"=>bin2hex($salt), "iv"=>bin2hex($iv), "encrypt"=>$encrypt]));
    return($return);
}

function isJson($string) {
    return ((is_string($string) &&
            (is_object(json_decode($string)) ||
            is_array(json_decode($string))))) ? true : false;
}
