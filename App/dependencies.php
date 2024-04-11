<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\UidProcessor;
use bjsmasth\Salesforce\Authentication\PasswordAuthentication;
use bjsmasth\Salesforce\CRUD;
use TFCLog\PDOLogger;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Psr\Container\ContainerInterface as c;
use App\Provider\EndUser\PDOEndUserProvider as EndUserProvider;
//use App\Provider\EndUser\FakeEndUserProvider as EndUserProvider;
use App\Repository\API\EndUserRepository;
use App\Repository\API\VoucherRepository;
use App\Repository\API\PassRepository;
use App\Repository\TFCBlue\AuthenticationRepository;
use App\Repository\API\ProductRepository;
use App\Repository\Base\ShopRepository;

use App\Repository\Logger\Database;
use App\Repository\Logger\DBLogger;

use App\Helper\Providers\Nexway;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
//use pmill\AwsCognito\CognitoClient;

use App\Provider\SFAccount\RealSFAccountProvider;
use App\Provider\SFContact\RealSFContactProvider;
use App\Provider\Cognito\RealCognitoProvider;
use App\Provider\Shop\RealShopProvider;
use App\Provider\Product\Redemption\RealRedemptionProvider;

(function (App $app) {
    $c = $app->getContainer();

    /**
      * Admin DB
      *
      * @param Psr\Container\ContainerInterface $c
      * @return \PDO
      */
    $c['db.admin'] = function (c $c) {
        $s = $c->get('settings')['db']['admin'];
        return new \PDO('mysql:dbname=' . $s['database'] . ';host=' . $s['host'] . ';port=' . $s['port'] ?? 3306, $s['user'], $s['pass'], $s['opts']);
    };

    /**
      * TFC DB
      *
      * @param Psr\Container\ContainerInterface $c
      * @return \PDO
      */
    $c['db.tfc'] = function (c $c) {
        $s = $c->get('settings')['db']['tfc'];
        return new \PDO('mysql:dbname=' . $s['database'] . ';host=' . $s['host'] . ';port=' . $s['port'] ?? 3306, $s['user'], $s['pass'], $s['opts']);
    };


    /**
      * Log DB
      *
      * @param Psr\Container\ContainerInterface $c
      * @return \PDO
      */
    $c['db.log'] = function (c $c) {
        $s = $c->get('settings')['db']['log'];
        return new \PDO('mysql:dbname=' . $s['database'] . ';host=' . $s['host'] . ';port=' . $s['port'] ?? 3306, $s['user'], $s['pass'], $s['opts']);
    };

    /**
     * TFC DB
     *
     * @param Psr\Container\ContainerInterface $c
     * @return \PDO
     */
    $c['db.tfc'] = function (c $c) {
        $s = $c->get('settings')['db']['tfc'];
        return new \PDO('mysql:dbname=' . $s['database'] . ';host=' . $s['host'] . ';port=' . $s['port'] ?? 3306, $s['user'], $s['pass'], $s['opts']);
    };

    /**
     * Logger
     *
     * https://github.com/Seldaek/monolog
     *
     * @param Psr\Container\ContainerInterface $c
     * @return Monolog\Logger
     */
    $c['logger'] = function (c $c) : Logger {
        $s = $c->get('settings')['logger'];
        $l = new Logger($s['name']);
        $l->pushProcessor(new UidProcessor);
        $l->pushHandler(new StreamHandler($s['path'], $s['level']));
        return $l;
    };
    
    $c['tfcLogger'] = function (c $c) : TFCLog\TFCLogger {
        $s = $c->get('settings')['logger'];
        $l = new TFCLog\TFCLogger($s['name']);
        return $l;
    };

    /**
    * PDOLogger
    *
    * https://github.com/Seldaek/monolog
    *
    * @param Psr\Container\ContainerInterface $c
    * @return TFC\PDOLogger
    */
    $c['pdologger'] = function (c $c) : PDOLogger {
        $s = $c->get('settings')['logger'];
        $l = new PDOLogger($s['name'], $c->get('db.log'));
        return $l;
    };

    /**
    * Id provider for generating UUIDs
    *
    * https://github.com/ramsey/uuid
    *
    * @param Psr\Container\ContainerInterface $c
    * @return App\Provider\Id\UuidProvider
    */
    $c['uuid'] = function (c $c) : \App\Provider\Id\UuidProvider {
        $f = new \Ramsey\Uuid\UuidFactory;
        $otc = new \Ramsey\Uuid\Codec\OrderedTimeCodec($f->getUuidBuilder());
        $f->setCodec($otc);

        return new \App\Provider\Id\UuidProvider($f);
    };

    /**
      * Salesforce
      *
      * @param Psr\Container\ContainerInterface $c
      * @return \Phpforce\SoapClient\Client
      */
    $c['salesforce'] = function (c $c) {
        $s = $c->get('settings')['salesforce'];

        $f = new PasswordAuthentication($s['access']);
        $f->authenticate();
        $c = new CRUD();
        return $c;
    };

    /**
     * DBLogger
     * 
     * @param Psr\Container\ContainerInterface $c
     * @return \App\Repository\Logger\DBLogger
     */
    $c['dbLogger'] = function (c $c) : \App\Repository\Logger\DBLogger {

        //$db = new Database($c->get('db.tfc'));

        return new \App\Repository\Logger\DBLogger($c->get('db.tfc'));
    };

    /**
    * Cache Provider to deal with Cache
    *
    * @return Slim\HttpCache\CacheProvider
    */
    $c['cache'] = function () : \Slim\HttpCache\CacheProvider {
        return new \Slim\HttpCache\CacheProvider();
    };
    
    /**
     * AWS CognitoIdentity
     *
     * @param Psr\Container\ContainerInterface $c
     * @return \AWS\CognitoIdentity\CognitoIdentityProviderClient
     */
    $c['cognito'] = function (c $c) : RealCognitoProvider {
        $config = $c->get('settings')['cognito'];
        $aws = new \Aws\Sdk($config);
        $cognitoClient = $aws->createCognitoIdentityProvider();
//        $client = new \pmill\AwsCognito\CognitoClient($cognitoClient);
        $client = new \App\Provider\Cognito\RealCognitoClient($cognitoClient);
        
        $client->setAppClientId($config['appClientId']);
        $client->setAppClientSecret($config['appClientSecret']);
        $client->setRegion($config['region']);
        $client->setUserPoolId($config['userPoolId']);
        
        $provider = new RealCognitoProvider($c['db.admin'], $c['tfcLogger'], $client);
        return $provider;
    };

    /**
     * EndUser repository
     *
     * @param Psr\Container\ContainerInterface $c
     * @return EndUserRepository
     */
    $c['endUserRepository'] = function (c $c) : EndUserRepository {
        $provider = new EndUserProvider($c->get('db.admin'), $c->get('db.tfc'), $c->get('db.log'), $c->get('salesforce'));
        $sfRedemptionProvider = new \App\Provider\SFRedemption\RealSFRedemptionProvider($c['tfcLogger'], $c->get('salesforce'));
        $apiUserProvider = new \App\Provider\APIUser\RealAPIUserProvider($c->get('db.admin'));
        $shopProvider = new \App\Provider\Shop\PDOShopProvider($c->get('db.admin'), $c->get('db.log'), $c->get('db.tfc'), $c->get('salesforce'));
        return new EndUserRepository($provider, $sfRedemptionProvider, $c->get('uuid'), $apiUserProvider, $shopProvider);
    };

    /**
     * Voucher repository
     *
     * @param Psr\Container\ContainerInterface $c
     * @return VoucherRepository
     */
    $c['voucherRepository'] = function (c $c) : VoucherRepository {
        $providers['voucherProvider'] = new \App\Provider\Voucher\RealVoucherProvider($c->get('db.admin'), $c->get('db.tfc'), $c->get('salesforce'));
        $providers['endUserProvider'] = new EndUserProvider($c->get('db.admin'), $c->get('db.tfc'), $c->get('db.log'), $c->get('salesforce'));
        return new VoucherRepository($providers);
    };

    /**
     * Pass repository
     *
     * @param Psr\Container\ContainerInterface $c
     * @return PassRepository
     */
    $c['passRepository'] = function (c $c) : PassRepository {
        $providers['passProvider'] = new \App\Provider\Pass\RealPassProvider($c->get('db.admin'), $c->get('salesforce'));
        return new PassRepository($providers);
    };
    
    /**
     * Authentication repository
     *
     * @param Psr\Container\ContainerInterface $c
     * @return AuthenticationRepository
     */
    $c['tfcBlueAuthenticationRepository'] = function (c $c) : AuthenticationRepository {
        $accountProvider = new \App\Provider\Account\AccountProvider($c['tfcLogger'], $c['salesforce']);
        $contactProvider = new \App\Provider\Contact\ContactProvider($c['tfcLogger'], $c['salesforce']);
        
        $provider = new AuthenticationRepository($c['cognito'], $accountProvider, $contactProvider, $c['uuid']);
        
        return $provider;
    };

    /**
     * Shop repository
     *
     * @param Psr\Container\ContainerInterface $c
     * @return ShopRepository
     */
    $c['shopRepository'] = function (c $c) : ShopRepository {
        $provider = new RealShopProvider($c->get('db.admin'), $c->get('db.log'), $c->get('db.tfc'), $c->get('salesforce'));
        return new ShopRepository($provider, $c->get('uuid'));
    };

    /**
     * 
     * @param Psr\Container\ContainerInterface $c
     * @return \\Nexway
     */
    $c['Nexway'] = function (c $c) : Nexway {
        $s = $c->get('settings')['providers'];
        $provider = new Nexway($c->get('db.tfc'), $c->get('salesforce'), $s['nexway']);
        return $provider; 
    };


    /**
     * Redemption repository
     *
     * @param Psr\Container\ContainerInterface $c
     * @return ProductRepository
     */
    $c['ProductRepository'] = function (c $c) : ProductRepository {
        $providers['nexwayRedemptionProvider'] = new RealRedemptionProvider($c->get('db.tfc'), $c->get('salesforce'), $c->get('dbLogger'), $c['Nexway']);
        return new ProductRepository($providers);
    };

    

})($app);
