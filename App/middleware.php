<?php
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

(function (App $app) {

    $c = $app->getContainer();

    //$app->add(new \Tuupola\Middleware\HttpBasicAuthentication($c->get('settings')['HttpBasicAuthentication']));

    // JSON Token authentication
    $app->add(new \Slim\Middleware\JwtAuthentication(
        [
            "attribute" => "jwt",
            "path" => "/v2/*",
            "iat" => time(),
            "exp" => strtotime('+2 hours', strtotime( date("Y-m-d H:i:s") )),
            "secret" => getenv("JWT_SECRET"),
            "error" => function ($request, $response, $arguments) {
                $data = [];
                $data["error"] = "ERR401";
                $data["message"] = $arguments["message"];
                return $response->write(json_encode($data, JSON_UNESCAPED_SLASHES));
            }
        ]
    ));

    //CORS, for cross browser security
    $app->add(
        new \CorsSlim\CorsSlim(
            $c->get('settings')['corsOptions']
        )
    );

    //Caching system
    $app->add(
        new \Slim\HttpCache\Cache(
            $c->get('settings')['Cache']['name'], 
            $c->get('settings')['Cache']['time']
        )
    );

})($app);
