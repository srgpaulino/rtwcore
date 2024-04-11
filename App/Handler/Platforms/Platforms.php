<?php

namespace App\Handler\Platforms;

class Platforms
{
    const UNDETECTED = 1;
    const IOS        = 2;
    const ANDROID    = 4;
    // WINPHO7 includes 6.5
    const WINPHO7    = 8;
    // WINPHO8, for the time being, will cover versions greater than 8 as well
    const WINPHO8    = 16;
    const BLACKBERRY = 32;
    const ALLMOBILE  = 63;
    const WINDOWS    = 64;
    const OSX        = 128;
    const LINUX      = 256;
    const ALLDESKTOP = 448;
    const ALL = 511;

    static $device;

    static $mobile;

    static $detected;

    public static function getSettings($contentType)
    {
        $allSettings = Settings::app()->mobileRedemptions;
        
        // If settings is either true or false, return
        if ($allSettings === true || $allSettings === false) {
            return $allSettings;
        }

        // If settings for content type don't exist
        if (!isset($allSettings[$contentType]) && !isset($allSettings[$contentType]['allowed'])) {
            return false;
        }

        // Loop through allowed values and calculate final binary value
        $finalBinary = 0;
        foreach ($allSettings[$contentType]['allowed'] as $binary) {
            $finalBinary = $finalBinary | $binary;
        }

        // If disallowed values are set, loop through and remove them from final binary value
        if (isset($allSettings[$contentType]['disallowed'])) {
            foreach ($allSettings[$contentType]['disallowed'] as $binary) {
                $finalBinary = $finalBinary & ~$binary;
            }
        }

        return $finalBinary;
    }

    public static function getPlatformId()
    {
        // Get platform information
        $platform = self::platformInfo();

        if (empty($platform) || !isset($platform['id'])){
            return false;
        }

        return $platform['id'];
    }

    public static function platformInfo($class = null)
    {
        // First set a wurfl device and check if it thinks it's mobile
        Library_Platforms_Mobile::setWurfl();

        // Check to see if it has already been detected. No use detecting again
        if ($class === null && !empty(self::$detected)) {
            if (self::$detected['name'] != 'undetected' || self::$mobile == true) {
                return self::$detected;
            }
        }

        // If class is passed, use it
        if ($class !== null) {
            $class = 'Library_Platforms_' . $class;
        } else {
            // If device is mobile get platform info from mobile platforms class, otherwise use desktop
            $class = 'Library_Platforms_';
            if (self::$mobile == true) {
                $class .= 'Mobile';
            }
            else {
                $class .= 'Desktop';
            }
        }

        $info = $class::getPlatformInfo();

        if (empty($info)) {
            $info = array(
                'id'=>Library_Platforms::UNDETECTED,
                'name'=>'undetected',
                'compactName'=>'undetected'
            );
        }
        else{
            $info['compactName'] = str_replace(' ', '', $info['name']);
        }

        self::$detected = $info;
        return $info;
    }

    public static function deviceInfo(\PDO $db){
        $ua = $_SERVER['HTTP_USER_AGENT'];

        $date = date('Y-m-d H:i:s');

        // Check if the UA string is in the match table, if so update some values and return
        // If not, continue
        $stmt = $db->prepare('
            SELECT md.*
              FROM useragents as ua,
                   useragents_devices AS ud,
                   mobile_devices as md
             WHERE ud.useragent_id = ua.id
               AND ud.device_id = md.id
               AND ua.ua_string = :ua
        ');
        $stmt->execute(array('ua'=>$ua));
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($results) && $results !== false) {
            $stmt = $db->prepare('UPDATE useragents SET last_seen = :date WHERE ua_string = :ua');
            $stmt->execute(array('date'=>$date, 'ua'=>$ua));
            
            return $results;
        }

        // Check if the UA string is in the patterns/override table, if so then add row to UA table
        // If not continue
        $stmt = $db->prepare('
            SELECT md.*
              FROM useragents_patterns AS ua,
                   useragents_patterns_devices AS ud,
                   mobile_devices AS md
             WHERE ud.useragent_pattern_id = ua.id
               AND ud.device_id = md.id
               AND :ua
              LIKE ua.ua_pattern
          ORDER BY ua.priority DESC
             LIMIT 1
        ');
        $stmt->execute(array('ua'=>$ua));
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($results) && $results !== false) {
            Library_Platforms::addUserAgent($results, $ua, $db);
            return $results;
        }

        // If it exists we know the device
        // If not, then we'll search the UA string using the device models in the db

        // Detect platform first, as each platform will have its own device detection
        $platformInfo = Library_Platforms::platformInfo('Mobile');
        if (!isset($platformInfo['class'])) {
            return array();
        }

        $devices = $platformInfo['class']::getDevices($db);

        if (empty($devices) || isset($devices['model']) && $devices['model'] == 'undetected') {
            return array();
        }

        Library_Platforms::addUserAgent($devices, $ua, $db);
        return $devices;
    }

    private static function addUserAgent($devices, $ua, $db){
        $date = date('Y-m-d H:i:s');

        // Add full UA to useragents table
        $stmt = $db->prepare('INSERT INTO useragents (ua_string, first_seen, last_seen) VALUES (:ua, :date, :date)');
        $stmt->execute(array('ua'=>$ua, 'date'=>$date));
        $rowId = $db->lastInsertId();

        // Add rows to link table
        foreach($devices as $device){
            $db->query('INSERT INTO useragents_devices (useragent_id, device_id) VALUES ("' . $rowId . '", "' . $device['id'] . '")');
        }
    }
}

