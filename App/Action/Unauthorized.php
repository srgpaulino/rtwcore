<?php
namespace App\Action;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;


/**
 * NoFunction action class. Returned when the requested function does not exist
 **/
final class Unauthorized {

    private $logger;
    private $settings;

    public function __construct(Logger $logger, Collection $settings)
    {
        $this->logger = $logger;
        $this->settings = $settings;
    }

    public function __invoke(Request $request, Response $response, array $args) : Response
    {
        if (!isset($args['version']) || is_nan($args['version'])) {
            $args['version'] = $this->settings['version'];
        }

        $this->logger->error("E401", json_encode(["message" => 'Requested method is not authorized for ' . $_SERVER['REQUEST_METHOD']]));
        $response = $response->withJson(
                [
                    "error"     => "ERR401",
                    "message"   => "Requested method is not authorized for " . $_SERVER['REQUEST_METHOD']
                ]
            )
            ->withStatus(401);

        //Return NoFunction response. Expected HTTP Response 402.
        return $response;
    }

}
