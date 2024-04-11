<?php

namespace App\Provider\EndUser;

use App\Domain\EndUser;
use PDO;
use App\Exception\DoesNotExistException;
use App\Exception\InvalidAttributeException;
use App\Exception\InvalidAuthenticationException;

class FakeEndUserProvider implements EndUserProvider {

    private $adminpdo;
    private $logpdo;
    private $fallback;
    //set of fake data
    private $repository = [
        '6ba7b810-9dad-11d1-80b4-00c04fd430c8' => [
            'id'            => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'TFCUserId'     => '7VBO7FVB877I6B',
            'clientUserId'  => 'h67b8yb68g',
            'loginName'     => 'spaulino',
            'name'          => 'Sergio',
            'lastName'      => 'Paulino',
            'coinsCount'    => '300',
            'geoId'         => 'GB',
            'language'      => 'EN',
            'email'         => 'neopaulino@gmail.com',
            'shop'          => '6'
        ],
        '6ba7b467-9dad-33d1-93b4-00c04fd306c8' => [
            'id'            => '6ba7b467-9dad-33d1-93b4-00c04fd306c8',
            'TFCUserId'     => 'B978OB3267V328Y',
            'clientUserId'  => 'n708hbiub7i6',
            'loginName'     => 'yfernandez',
            'name'          => 'Yolanda',
            'lastName'      => 'Fernandez',
            'coinsCount'    => '4360',
            'geoId'         => 'ES',
            'language'      => 'ES',
            'email'         => 'yfernandez@gmail.com',
            'shop'          => '6'
        ],
        '6ba7b274-9jdy-14d1-56b4-00c04fd846f0' => [
            'id'            => '6ba7b274-9jdy-14d1-56b4-00c04fd846f0',
            'TFCUserId'     => 'HN7P9B97B28623SD',
            'clientUserId'  => '69vyuvyy',
            'loginName'     => 'ttheron',
            'name'          => 'Tani',
            'lastName'      => 'Theron',
            'coinsCount'    => '140',
            'geoId'         => 'GB',
            'language'      => 'EN',
            'email'         => 'ttheron@gmail.com',
            'shop'          => '14'
        ]
    ];

    public function __construct(PDO $adminpdo, PDO $logpdo){
		$this->adminpdo = $adminpdo;
        $this->logpdo = $logpdo;
	}

    public function getAll(string $filter = null, string $apiUser) : Array
    {
        return [
            'data' => $this->repository
        ];
    }

	public function getById(string $id, string $apiUser) : Array
    {
        if(isset($this->repository[$id])){
            return [
                'data' => $this->repository[$id]
            ];
        } else {
            throw new DoesNotExistException();
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
