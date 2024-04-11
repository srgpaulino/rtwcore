<?php

namespace Tests;

use Slim\App;
use GuzzleHttp\Client as GuzzleHttp;
use PHPUnit\Framework\TestCase;

class BaseTest extends TestCase {

	protected $app;
	protected $c;
	protected $client;

	public static $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyIjoic3BhdWxpbm8iLCJzY29wZXMiOlsiZW5kdXNlciIsImVuZHVzZXJzIl19.io1TawumVNrnVzk3Sopc9sHsdOnJH6pgmMup9ZUIkJY';

	//Setup test class
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

	//Test Authorization Fail
	public function testAuthFail()
	{
		$response = $this->client->get(
			'/v2/test',
			[
				'verify' => false,
				'http_errors' => false
			]
		);

		$this->assertSame($response->getStatusCode(), 401);
	}

	//Test Authorization
	public function testAuth()
	{
		$response = $this->client->get(
			'/v2/test',
			[
				'verify' => false,
				'http_errors' => false,
				'headers' => [
                    'Authorization' => 'Bearer ' . self::$token,
                    'Accept'        => 'application/json'
                ]
			]
		);

		$this->assertSame($response->getStatusCode(), 200);
	}

	//Test Test Action
	public function testTestResponse()
	{
		$response = $this->client->get(
			'/v1/test',
			[
				'verify' => false,
				'http_errors' => false,
				'headers' => [
                    'Authorization' => 'Bearer ' . self::$token,
                    'Accept'        => 'application/json'
                ]
			]
		);

		$this->assertSame($response->getStatusCode(), 200);
		$data = json_decode($response->getBody(), true);
		$this->assertArrayHasKey('message', $data);
		$this->assertEquals('SlimTFC Core', $data['message']);
	}

	//Test Version Action
	public function testVersionResponse()
	{
		$response = $this->client->get(
			'/v1/version',
			[
				'verify' => false,
				'http_errors' => false,
				'headers' => [
                    'Authorization' => 'Bearer ' . self::$token,
                    'Accept'        => 'application/json'
                ]
			]
		);

		$this->assertSame($response->getStatusCode(), 200);
		$data = json_decode($response->getBody(), true);
		$this->assertArrayHasKey('version', $data);
		$this->assertEquals($this->c->get('settings')['version'], $data['version']);
	}

}
