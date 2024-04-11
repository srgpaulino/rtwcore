<?php

namespace App\Provider\APIUser;

use PDO;

class RealAPIUserProvider implements APIUserProvider{

    private $adminpdo;
    
    public function __construct(PDO $adminpdo)
    {
        $this->adminpdo = $adminpdo;
    }

    public function validateKey($apiUser, $key) : bool 
    {

        try  {

            ddd("apiUser: " . $apiUser);
            ddd("key: " . $key);
            ddd("query:");
            ddd("SELECT count(id) as `count` FROM `users` WHERE `id`=$apiUser AND `private_key`='$key'");

            $sql = "SELECT count(id) as `count` FROM `users` WHERE `id`=:apiUser AND `private_key`=:key";
            $stmt = $this->adminpdo->prepare($sql);
            $stmt->bindValue(":apiUser", $apiUser, PDO::PARAM_STR);
            $stmt->bindValue(":key", $key, PDO::PARAM_STR);
            $stmt->execute();

            $res = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            ddd("validate key result: ");
            ddd($res);

            if($res[0]['count']==1) {
                return true;
            }
            return false;

        } catch (Exception $e) {
            throw $e;
        }

    }

    /**
     * Fallback/secondary Provider
     *
     * When needing to save data in multiple places, e.g. MySQL & Salesforce, attach additional providers here
     *
     * @param EndUserProvider $fallback
     */
    public function attach(EndUserProvider $fallback)
    {
        $this->fallback = $fallback;
    }
    
}
