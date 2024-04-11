<?php

namespace App\Action\TFCBlue;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;
use App\Repository\TFCBlue\AuthenticationRepository as Repository;

/**
 * Perform Registration
 */

final class Register {

    private $logger;
    private $repository;
    private $settings;

    public function __construct(Logger $logger, Collection $settings, Repository $repository) {
        $this->logger = $logger;
        $this->settings = $settings;
        $this->repository = $repository;
    }

    public function __invoke(Request $request, Response $response, array $args): response {
        $data = $request->getParsedBody();
        try {
            if (isset($args['version']) && ($args['version'] < 2 || intval($args['version']) > intval($this->settings['version']))) {
                throw new \App\Exception\MethodNotImplementedException('Requested method is not implemented for v' . $args['version'] . '. Min version: 2');
            }

            $registerRequest = new \App\Domain\TFCBlue\Authentication\RegistrationRequest($data);

            $this->logger->info("Registration strated.");

            $account = $this->repository->register($registerRequest);

            $return = [
                'username' => $account->username,
                'status' => $account->status,
            ];

            $this->logger->info("Registration complete");

            $response = $response->withJson($return)->withStatus(200);
        } catch (\Exception $e) { //Capture any Exceptions being thrown
            unset($data['password']);
            unset($data['repeatpassword']);
            $this->logger->error($e->getMessage(), json_encode($data));
            $response = $response->withJson(
                    [
                        "error" => "ERR" . $e->getCode(),
                        "message" => $e->getMessage()
                    ]
                )
                ->withStatus($e->getCode());
        } catch (Error $e) { //Capture any Errors being thrown
            $this->logger->critical($e->getMessage());
            $response = $response->withJson(
                    [
                        "error" => "ERR" . $e->getCode(),
                        "message" => $e->getMessage()
                    ]
                )
                ->withStatus($e->getCode());
        }

        //return Register. Expected HTTP Response 200
        return $response;
    }

}