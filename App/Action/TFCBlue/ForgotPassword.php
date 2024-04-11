<?php

namespace App\Action\TFCBlue\ForgotPassword;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;
use Slim\HttpCache\CacheProvider;
use App\Repository\TFCBlue\AuthenticationRepository as Repository;

/** 
 * Perform ForgotPassword
 */

final class ForgotPassword
{
    private $logger;
    private $cache;
    private $repository;
    private $saml;
    private $apiuser;
    private $auth;
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
        ddd("Reached at Login: ");
        ddd(json_encode($data));
        $errors = [];

        try {
            $params = $request->getQueryParams();

            if (isset($args['version']) && ($args['version']<2 || intval($args['version'])>intval($this->settings['version']))) {
                throw new \App\Exception\MethodNotImplementedException('Requested method is not implemented for b' . $args['version'] . '. Min version: 2');
            }

            //API username pulled from the JSON Token
            if (!isset($request->getAttribute("jwt")->user)) {
                throw new \App\Exception\InvalidAuthenticationException();
            }
            $apiUser = $request->getAttribute("jwt")->user;

            //Setting Cache headers
            $response = $this->cache->withExpires($response, '+ 60 minutes');
            $response = $this->cache->withLastModified($response, '- 60 minutes');
            
            // verify user Authentication
            $this->saml = json_decode($params['request']);
	        $this->saml->user->session = bin2hex(openssl_random_pseudo_bytes(16));
            $this->clientData = json_encode($this->saml->user);

            $this->apiuser = $this->saml->apiuser;
            $this->apikey = $this->saml->apikey;
            $this->auth = $this->repository->forgotPassword($this->clientData, $this->apiuser, $this->apikey);
            $this->logger->info("Reset password completed.");
            //set Cache ETag with encrypted response
            $response = $this->cache->withEtag($response, md5(json_encode($this->auth)));
            $response = $response->withJson($this->auth)->withStatus(200);

        } catch (Exception $e) {//Capture any Exceptions being thrown
            $this->logger->error($e->getCode()->toString(), $e->getMessage());
            $response = $response->withJson(
                    [
                        "error"     => "ERR" . $e->getCode(),
                        "message"   => $e->getMessage()
                    ]
                )
                ->withStatus($e->getCode());
        } catch (Error $e) {//Capture any Errors being thrown
            $this->logger->critical($e->getCode()->toString(), $e->getMessage());
            $response = $response->withJson(
                    [
                        "error"     => "ERR" . $e->getCode(),
                        "message"   => $e->getMessage()
                    ]
                )
                ->withStatus($e->getCode());
        }

        //return Login. Expected HTTP Response 200
        return $response;
    }
}