<?php

namespace App\Action\TFCBlue;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;
use App\Repository\TFCBlue\OrderRepository as Repository;

/**
 * Perform Order placement
 */
final class CreateOrder {

    private $logger;
    private $settings;
    private $repository;

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

            $registerRequest = new \App\Domain\TFCBlue\Orders\OrderRequest($data);

//            dd($registerRequest);
//
            $this->logger->info("Order palacement strated.");
//
            $account = $this->repository->place($registerRequest);
            
//
//            $return = [
//                'username' => $account->username,
//                'status' => $account->status,
//            ];
//
//            $this->logger->info("Registration complete");
//
            $response = $response->withJson($return)->withStatus(200);
        } catch (\Exception $e) { //Capture any Exceptions being thrown
            
            $this->logger->error($e->getMessage(), json_encode($data));
            $response = $response->withJson(
                    [
                        "error" => "ERR" . $e->getCode(),
                        "message" => $e->getMessage()
                    ]
                )
                ->withStatus($e->getCode());
        } catch (\Error $e) { //Capture any Errors being thrown
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
