<?php
namespace App\Action;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;
use PVO\Auth;

/**
 * Test Action class
 **/
final class Test {

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

        $this->logger->info(json_encode(["message" => "Test Action Invoked", "args" => $args]));
        return $response->withJson(['message' => 'SlimTFC Core'])->withStatus(200);
    }

}
