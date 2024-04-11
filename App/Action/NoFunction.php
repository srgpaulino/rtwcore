<?php
namespace App\Action;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;


/**
 * NoFunction action class. Returned when the requested function does not exist
 **/
final class NoFunction {

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

        ddd("entered no function action");

        $this->logger->error('E501', json_encode(['message' => 'Requested method "' . $args['function'] . '" is not implemented for v' . $args['version']]));
        $response = $response->withJson(
                [
                    "error"     => "ERR501",
                    "message"   => "Requested method '" . $args['function'] . "' is not implemented for v" . $args['version']
                ]
            )
            ->withStatus(501);

        return $response;
    }

}
