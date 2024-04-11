<?php
namespace App\Action\API\Voucher;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;
use App\Repository\API\VoucherRepository as Repository;
use App\Domain\API\Voucher\ListVoucherWalletRequest as ValidRequest;

/**
 * ListVouchers Voucher
 */

final class ListVoucherWallet
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
        try {
            if (isset($args['version']) && ($args['version'] < 2 || intval($args['version']) > intval($this->settings['version']))) {
                throw new \App\Exception\MethodNotImplementedException('Requested method is not implemented for v' . $args['version'] . '. Min version: 2');
            }

            if(!(isset($args['user']))) 
            {  
                throw new \InvalidArgumentException("Missing fields in request. Required fields: code and format");
            }

            $data = [
                'end_user' => $args['user']
            ];

            $validRequest = new ValidRequest($data);

            $this->logger->info("Voucher with wallets listing started.");
            
            $return = $this->repository->ListVoucherWallet($validRequest);

            $this->logger->info("Voucher with wallets listing complete");

            $response = $response->withJson($return)->withStatus(200);
        } catch (\Exception $e) { //Capture any Exceptions being thrown
            $this->logger->error($e->getMessage(), json_encode($data));
            $response = $response->withJson(
                [
                    "error" => "ERR" . $e->getCode(),
                    "message" => $e->getMessage()
                ]
            )->withStatus($e->getCode());
        } catch (\Error $e) { //Capture any Errors being thrown
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
