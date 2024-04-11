<?php

namespace App\Action\Admin;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;
use App\Repository\TFCBlue\AuthenticationRepository as Repository;

/** 
 * Perform Login
 */

final class LoginAdmin
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

        try {
            if (isset($args['version']) && ($args['version']<2 || intval($args['version'])>intval($this->settings['version']))) {
                throw new \App\Exception\MethodNotImplementedException('Requested method is not implemented for v' . $args['version'] . '. Min version: 2');
            }
            
            $data['isAdmin'] = true;
            $loginRequest = new \App\Domain\TFCBlue\Authentication\LoginRequest($data);

            $this->logger->info("Login started.");
            
            $account = $this->repository->login($loginRequest);
            
            $noLongerWithCompany = $this->repository->getSFContact($account->email);

            // check if user account id is matched with tfc sales force account id AND noLonger field is true.
            if($account->sfAccountId !== $this->settings['salesforce']['tfc_sf_account_id'] || $noLongerWithCompany == 1):
                throw new \Exception('No longer with company', 422);
            else:
                $return = [
                'name' => $account->name,
                'email' => (string) $account->email,
                'cognitoId' => $account->username,
                'sfAccountId' => $account->sfAccountId,
                'sfContactId' => $account->sfContactId,
                'status' => $account->status
            ];
            endif;

            $this->logger->info("Login completed.");
            
            $response = $response->withJson($return)->withStatus(200);

        } catch (\Exception $e) {//Capture any Exceptions being thrown
            unset($data['password']);
            $this->logger->error($e->getMessage(), json_encode($data));
            
            $response = $response->withJson(
                    [
                        "error"     => "ERR" . $e->getCode(),
                        "message"   => $e->getMessage()
                    ]
                )
                ->withStatus($e->getCode());
        } catch (\Error $e) {//Capture any Errors being thrown
            unset($data['password']);
            $this->logger->critical($e->getMessage());
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