<?php

namespace App\Handler\Platforms;

class PlatformsMobile extends Platforms
{

    /**
     * Array of regex strings to match against mobile user agents and the associated class string to append
     * @var array
     */
    private static $regex = array(
        'android' => 'Android',
        'ip(hone|od|ad)' => 'Ios',
        'windows phone' => 'Windowsphone',
        'bb10|blackberry|RIM Tablet' => 'Blackberry'
    );

    /**
     * For the time being just used to initially detect mobile device
     */
    static function setWurfl()
    {
        if (empty(self::$device)) {
            self::$device = Loader::load('Library_Wurfl')->manager()->getDeviceForHttpRequest($_SERVER);

            if(self::$device->getCapability('is_wireless_device') == 'true' || self::$device->getCapability('is_tablet') == 'true') {
                self::$mobile = true;
            }
        }
    }

    static function getPlatformInfo()
    {
        // If we couldn't match, return empty array which will be handled elsewhere
        if (!$class = self::detectPlatform()) {
            return array();
        }

        // If we found a platform, get details from class
        $className = 'Library_Platforms_Mobile_' . $class;
        $details = $className::getDetails();

        return $details;
    }

    static function detectPlatform($ua = null){
        if ($ua === null) {
            $ua = $_SERVER['HTTP_USER_AGENT'];
        }

        // Try to match any of the strings and capture it
        foreach (self::$regex as $string=>$class) {
            preg_match('/'.$string.'/i', $ua, $match);
            if (!empty($match)) {
                return $class;
            }
        }
        
        // If we don't find anything return false
        return false;
    }

    static function getAllDeviceId(){
        $platform = Library_Platforms::platformInfo();

        if(!isset($platform['type']) || $platform['type'] !== 'mobile'){
            return false;
        }

        return $platform['class']::$allDeviceId;

    }

}