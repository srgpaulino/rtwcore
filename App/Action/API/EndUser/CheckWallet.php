<?php

namespace App\Action\API\EndUser;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;
use \Slim\HttpCache\CacheProvider;
use App\Repository\API\EndUserRepository as Repository;

/**
 * Perform CheckWallet
 **/
final class CheckWallet
{
    private $logger;
    private $cache;
    private $repository;
    private $origin;
    private $saml;
    private $shop;
    private $apiuser;
    private $iv;
    private $wallet;
    private $settings;

    public function __construct(Logger $logger, Collection $settings, CacheProvider $cache, Repository $repository)
    {
        $this->logger = $logger;
        $this->cache = $cache;
        $this->settings = $settings;
        $this->repository = $repository;
    }

    public function __invoke(Request $request, Response $response, array $args) : response
    {
        $data = $request->getParsedBody();
        $errors = [];

        try {
            $params = $request->getQueryParams();

            if (isset($args['version']) && ($args['version']<2 || intval($args['version'])>intval($this->settings['version']))) {
                throw new \App\Exception\MethodNotImplementedException('Requested method is not implemented for v' . $args['version'] . '. Min version: 2');
            }

            //API username pulled from the JSON Token
            if (!isset($request->getAttribute("jwt")->user)) {
                throw new \App\Exception\InvalidAuthenticationException();
            }
            $apiUser = $request->getAttribute("jwt")->user;

            //Setting Cache headers
            $response = $this->cache->withExpires($response, '+ 60 minutes');
            $response = $this->cache->withLastModified($response, '- 60 minutes');

            $endUser = json_encode($request->getAttribute("jwt")->userData);
            $shop = $request->getAttribute("jwt")->shop;
            $key = $request->getAttribute("jwt")->apiKey;

            $this->wallet = $this->repository->getWallet($endUser, $shop, $apiUser, $key);
            ddd("wallet: ");
            ddd($this->wallet);
            $this->logger->info("Wallet request completed.");
            //set Cache ETag with encrypted response
            $response = $this->cache->withEtag($response, md5(json_encode($this->wallet)));
            $response = $response->withJson($this->wallet)->withStatus(200);
        } catch (\Exception $e) { //Capture any Exceptions being thrown
            $this->logger->error($e->getCode(), $e->getMessage());
            $response = $response->withJson(
                    [
                        "error"     => "ERR" . $e->getCode(),
                        "message"   => $e->getMessage()
                    ]
                )
                ->withStatus($e->getCode());
        } catch (\Error $e) { //Capture any Errors being thrown
            $this->logger->critical(500, $e->getMessage());
            $response = $response->withJson(
                    [
                        "error"     => "ERR500",
                        "message"   => $e->getMessage()
                    ]
                )
                ->withStatus($e->getCode());
        }

        //return SignOn. Expected HTTP Response 200
        return $response;
    }
}
