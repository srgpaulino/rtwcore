<?php

namespace Tests\EndUser;

use Slim\App;
use GuzzleHttp\Client as GuzzleHttp;
use PHPUnit\Framework\TestCase;
use App\Domain\EndUser;

use App\Exception\InvalidObjectException;
use App\Exception\InvalidAttributeException;

class EndUserDomainTest extends TestCase {

    protected $app;
    protected $c;
    protected $client;
    protected $endUser;

    protected $fields = [
        'success' => [
            'id'            => null,
            'TFCUserId'     => 'TEST123',
            'clientUserId'  => 'CTEST987',
            'loginName'     => 'lorem',
            'name'          => 'John',
            'lastName'      => 'Doe',
            'coinsCount'    => 666,
            'geoId'         => 'GB',
            'language'      => 'en',
            'email'         => 'john.doe@email.fake',
            'shop'          => 6
        ],
        'failure' => [
            'TFCUserId'     => 'TEST456',
            'clientUserId'  => 'CTEST951',
            'name'          => 'Jane',
            'lastName'      => 'Smith',
            'coinsCount'    => 999,
            'geoId'         => 'ES',
            'language'      => 'es',
            'email'         => 'jane.smith@email.fake',
            'shop'          => 8
        ]
    ];

    public static $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyIjoic3BhdWxpbm8iLCJzY29wZXMiOlsiZW5kdXNlciIsImVuZHVzZXJzIl19.io1TawumVNrnVzk3Sopc9sHsdOnJH6pgmMup9ZUIkJY';

    public function setUp()
    {
        $this->endUser = new EndUser($this->fields['success']);
    }   
    
    public function testCreateSuccess()
    {
        $this->assertInstanceOf(EndUser::class, new EndUser($this->fields['success']));
    }

    /**
     * @expectedException App\Exception\InvalidObjectException
     */
    public function testCreateFail()
    {
        new EndUser($this->fields['failure']);
    }

    public function testGetFieldSuccess()
    {
        $this->assertEquals($this->fields['success']['TFCUserId'], $this->endUser->TFCUserId);
    }
    
    /**
     * @expectedException App\Exception\InvalidAttributeException
     */
    public function testGetFieldFail()
    {
        $this->endUser->voucher;
    }

    public function testSetFieldSuccess()
    {
        $this->endUser->loginName = 'ipsum';
        $this->assertEquals('ipsum', $this->endUser->loginName);
    }

    /**
     * @expectedException App\Exception\InvalidAttributeException
     */
    public function testSetFieldFail()
    {
        $this->endUser->voucher = 6789;
    }

    public function testClone()
    {
        $newEndUser = clone $this->endUser;

        $this->assertJsonStringEqualsJsonString(
            (String) $this->endUser,
            (String) $newEndUser
        );
    }

    /**
     * @expected String
     */
    public function testToString()
    {
        $this->endUser->id = null;
        $this->assertJsonStringEqualsJsonString(
            (String) $this->endUser, 
            json_encode($this->fields['success'])
        );
    }

}