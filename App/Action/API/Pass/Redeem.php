<?php
namespace App\Action\API\Pass;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;
use App\Repository\API\PassRepository as Repository;
use App\Domain\API\Pass\RedeemRequest as ValidRequest;

/**
 * Create Pass
 */

final class Redeem
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

    public function __invoke(Request $request, Response $response, array $args): response
    {
        $data = $request->getParsedBody();
        try {
            if (isset($args['version']) && ($args['version'] < 2 || intval($args['version']) > intval($this->settings['version']))) {
                throw new \App\Exception\MethodNotImplementedException('Requested method is not implemented for v' . $args['version'] . '. Min version: 2');
            }

            if (!isset($data['redeem'])) {
                $data['redeem'] = 1;
            }

            $validRequest = new ValidRequest($data);

            $this->logger->info("Pass creation started.");
            
            $return = $this->repository->redeem($validRequest);
            $return  = $return->getData();

            $this->logger->info("Pass creation complete");

            $response = $response->withJson($return)->withStatus(200);
        } catch (\Exception $e) { //Capture any Exceptions being thrown
            ddd("Exception: " . $e->getCode() . " " . $e->getMessage());
            $this->logger->error($e->getMessage(), json_encode($data));
            $response = $response->withJson(
                [
                    "error" => "ERR" . $e->getCode(),
                    "message" => $e->getMessage()
                ]
            )->withStatus($e->getCode());
        } catch (\Error $e) { //Capture any Errors being thrown
            ddd("Error: " . $e->getCode() . " " . $e->getMessage());
            $this->logger->critical($e->getMessage(), json_encode($data));
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
