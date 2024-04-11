<?php

namespace App\Action\TFCBlue;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;
use App\Repository\TFCBlue\AuthenticationRepository as Repository;

/** 
 * Perform Logout
 */

final class Logout
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
        $data = $request->getParsedBody();
        echo "Reached at Logout";
		print_r($data);

        try {
            if (isset($args['version']) && ($args['version']<2 || intval($args['version'])>intval($this->settings['version']))) {
                throw new \App\Exception\MethodNotImplementedException('Requested method is not implemented for v' . $args['version'] . '. Min version: 2');
            }

            $logoutRequest = new \App\Domain\TFCBlue\Authentication\LogoutRequest($data);

            $this->logger->info("Logout started.");

            $account = $this->repository->logout($logoutRequest);

            $return = [

            ];

            $this->logger->info("Logout completed.");

            $response = $response->withJson($return)->withStatus(200);

        } catch (Exception $e) {//Capture any Exceptions being thrown
            $this->logger->error($e->getMessage(), json_encode($data));
            $response = $response->withJson(
                    [
                        "error"     => "ERR" . $e->getCode(),
                        "message"   => $e->getMessage()
                    ]
                )
                ->withStatus($e->getCode());
        } catch (Error $e) {//Capture any Errors being thrown
            $this->logger->critical($e->getMessage());
            $response = $response->withJson(
                    [
                        "error"     => "ERR" . $e->getCode(),
                        "message"   => $e->getMessage()
                    ]
                )
                ->withStatus($e->getCode());
        }

        //return Logout. Expected HTTP Response 200
        return $response;
    }
}