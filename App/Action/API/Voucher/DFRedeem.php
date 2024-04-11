<?php
namespace App\Action\API\Voucher;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;
use App\Repository\API\VoucherRepository as Repository;
use App\Domain\API\Voucher\DFRedeemRequest as ValidRequest;


/**
 * Redeem Voucher
 */

final class DFRedeem {

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

            $this->logger->info("Entered duty free voucher redemption.");

            /*if (!isset($data['redeem'])) {
                $data['redeem'] = 1;
            }*/

            $requestdata = json_decode(json_encode($request->getAttribute("jwt")),1);

            $this->logger->info("Duty Free voucher redemption request: " . json_encode($requestdata));

            //voucher request pulled from the JSON Token
            if (empty($requestdata)) {
                throw new \App\Exception\InvalidAuthenticationException();
            }

            $validRequest = new ValidRequest($requestdata);

            $this->logger->info("Duty Free Voucher redemption started.");
            
            if($requestdata['action'] == "redeem" || $requestdata['action'] == "validate")
            {
                $return = $this->repository->dfRedeem($validRequest);
            }          

            $this->logger->info("Duty Free Voucher redemption complete");

            if($requestdata['action'] == "redeem"){
                $response = $response->withJson($return)->withStatus(201);
            }

            if($requestdata['action'] == "validate"){
                $response = $response->withJson($return)->withStatus(200);
            }
            
        } catch (\Exception $e) { //Capture any Exceptions being thrown
            $this->logger->error($e->getMessage(), json_encode($requestdata));
            $response = $response->withJson(
                [
                    "error" => "ERR" . $e->getCode(),
                    "message" => $e->getMessage()
                ]
            )->withStatus($e->getCode());
        } catch (\Error $e) { //Capture any Errors being thrown
            $this->logger->critical($e->getMessage(), json_encode($requestdata));
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