<?php

namespace Tests;

use Slim\App;
use GuzzleHttp\Client as GuzzleHttp;
use PHPUnit\Framework\TestCase;
use TFCLog\PDOLogger;

class LogTest extends TestCase {

	protected $app;
	protected $c;
    protected $client;
    protected $logger;
    protected $pdo;

	public static $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyIjoic3BhdWxpbm8iLCJzY29wZXMiOlsiZW5kdXNlciIsImVuZHVzZXJzIl19.io1TawumVNrnVzk3Sopc9sHsdOnJH6pgmMup9ZUIkJY';

	//Setup test class
	public function setUp()
    {
		$this->client = new GuzzleHttp([
			'base_uri' => 'https://api.local.tfc.test'
        ]);

        $this->pdo = new \PDO(
            'mysql:dbname=tfcapi;107.22.34.17;port=22',
            'tfc_admin',
            'iVSOs69mwjfjQ2O',
            null
        );

        $this->logger = new PDOLogger('bar', $this->pdo);
        $this->app = (new App($config));
		$this->c = $this->app->getContainer();
    }

    public function testLogException()
    {        
        $this->expectException(DynamoDbException::class);
    }

	//Test Log
	public function testLogInfo()
	{

        try {
            $response = $this->logger->logInfo(date('dmY h:i:s').': Testing log.', 'phpunit');
        } catch (\Exception $e) {
            $response = $e;
        }

		$this->assertTrue($response);
    }
    
    //Test Log
	public function testLog()
	{

        try {
            $response = $this->logger->logInfo(date('dmY h:i:s').': Testing log.', 'phpunit');
        } catch (\Exception $e) {
            $response = $e;
        }

		$this->assertTrue($response);
	}

}
