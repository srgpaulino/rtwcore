<?php

namespace Tests;

use Slim\App;
use GuzzleHttp\Client as GuzzleHttp;
use PHPUnit\Framework\TestCase;
use Domain\EndUser;

class EndUserActionTest extends TestCase {

    protected $app;
    protected $c;
    protected $client;
    protected $endUser;

    public static $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyIjoic3BhdWxpbm8iLCJzY29wZXMiOlsiZW5kdXNlciIsImVuZHVzZXJzIl19.io1TawumVNrnVzk3Sopc9sHsdOnJH6pgmMup9ZUIkJY';

    public function setUp()
    {
        $this->client = new GuzzleHttp([
			'base_uri' => 'https://api.local.tfc.test'
		]);

		$config = [
        	'settings' => [
	        	'displayErrorDetails' => 1,
				// Environment - local, staging, production
		        'environment' => 'local',
		        'version'     => '2.4.1',
		        'author'     => 'TFC International',
	        ],

        ];
        $this->app = (new App($config));
		$this->c = $this->app->getContainer();
    }

    /*public function testUserCreate()
    {

    }

    public function testUserDelete()
    {

    }

    public function testUserUpdate()
    {

    }*/

    public function testUserList()
    {
        $response = $this->client->get(
			'/v2/endusers',
			[
				'verify' => false,
				'http_errors' => false,
				'headers' => [
                    'Authorization' => 'Bearer ' . self::$token,
                    'Accept'        => 'application/json'
                ]
			]
		);

        var_dump($response);

		$this->assertSame($response->getStatusCode(), 200);
    }

    /*public function testUserGet()
    {

    }

    public function testUserToggle()
    {

    }

    public function testUserSSO()
    {

    }*/

}