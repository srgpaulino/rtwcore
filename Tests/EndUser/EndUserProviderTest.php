<?php

namespace Tests\EndUser;

use Slim\App;
use GuzzleHttp\Client as GuzzleHttp;
use PHPUnit\Framework\TestCase;
use Provider\EndUser\MockEndUserProvider;
use Provider\EndUser\ProdEndUserProvider;
use Provider\EndUser\StgEndUserProvider;

class EndUserProviderTest extends TestCase {

    protected $app;
    protected $c;
    protected $client;
    protected $providers;

    public static $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyIjoic3BhdWxpbm8iLCJzY29wZXMiOlsiZW5kdXNlciIsImVuZHVzZXJzIl19.io1TawumVNrnVzk3Sopc9sHsdOnJH6pgmMup9ZUIkJY';

    public function setUp()
    {

    }   
    
    

}