<?php

namespace App\Action\Admin;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;
use App\Repository\TFCBlue\AuthenticationRepository as Repository;

/** 
 * Fetch Company Users List
 */

final class UsersList
{
    private $logger;
    private $repository;
    private $settings;

    public function __construct(Logger $logger, Collection $settings, Repository $repository)
    {
        $this->logger = $logger;
        $this->settings = $settings;
        $this->repository = $repository;
    }

    public function __invoke(Request $request, Response $response, array $args) : response
    {
        try {
            if (isset($args['version']) && ($args['version']<2 || intval($args['version'])>intval($this->settings['version']))) {
                throw new \App\Exception\MethodNotImplementedException('Requested method is not implemented for v' . $args['version'] . '. Min version: 2');
            }
            
            $contacts = $this->repository->getCompanyContacts($this->settings['salesforce']['tfc_sf_account_id']);
            
            $response = $response->withJson($contacts)->withStatus(200);

        } catch (\Exception $e) {//Capture any Exceptions being thrown
            $this->logger->error($e->getMessage(),"Exception occurred");
            
            $response = $response->withJson(
                    [
                        "error"     => "ERR" . $e->getCode(),
                        "message"   => $e->getMessage()
                    ]
                )
                ->withStatus($e->getCode());
        } catch (\Error $e) {//Capture any Errors being thrown
            $this->logger->critical($e->getMessage(),"Error occurred");
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