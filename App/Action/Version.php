<?php
namespace App\Action;

use TFCLog\PDOLogger as Logger;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Collection;
use \Slim\HttpCache\CacheProvider;
use \App\Provider\Id\UuidProvider;

/**
 * Version Action class
 **/
final class Version {

    private $logger;
    private $settings;
    private $cache;
    private $uuid;

    public function __construct(Logger $logger, Collection $settings, CacheProvider $cache, UuidProvider $uuid)
    {
        $this->logger = $logger;
        $this->settings = $settings;
        $this->cache = $cache;
        $this->uuid = $uuid;
    }

    public function __invoke(Request $request, Response $response, array $args) : Response
    {

        if (!isset($args['version']) || is_nan($args['version'])) {
            $args['version'] = $this->settings['version'];
        }

        $this->logger->info(json_encode(["message" => "Version requested.", "args" => $args]));

        //set cache headers
        $response = $this->cache->withEtag($response, md5($this->settings['version']));
        $response = $this->cache->withExpires($response, '+ 60 minutes');
        $response = $this->cache->withLastModified($response, '- 60 minutes');

        $response = $response->withJson(
            [
              'version' => $this->settings['version'],
              'author'  => $this->settings['author']
            ]
        )->withStatus(200);

        return $response;
    }

}
