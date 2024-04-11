<?php

namespace App\Action\TFCBlue;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;
use App\Repository\TFCBlue\OrderRepository as Repository;

/**
 * Perform Payment Intent
 */
final class PaymentIntent {

    private $logger;
    private $settings;
    private $repository;
    private $eloquent;

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
            $intentRequest = new \App\Domain\TFCBlue\Orders\IntentRequest($data);

            $this->logger->info("Payment intent strated.");

            $intent = $this->repository->paymentIntent($intentRequest);

            $return = $intent;
//            $return = [
//                'intent_secret_key' => $intent['secret_key']
//                'status'            => $intent['status']
//            ];

            $this->logger->info("Payment intent complete");

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

        return $response;
    }

}
