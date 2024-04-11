<?php
use App\Handler;
use Psr\Container\ContainerInterface as c;
use Slim\App;

(function (App $app) {

    $c = $app->getContainer();

    $c['errorHandler'] = function (c $c) {
        return new Handler\Error(
            $c->get('logger'),
            $c->get('settings')['displayErrorDetails'],
            $c->get('settings')['logTrace']
        );
    };

    $c['phpErrorHandler'] = function (c $c) {
        return new Handler\PhpError(
            $c->get('logger'),
            $c->get('settings')['displayErrorDetails'],
            $c->get('settings')['logTrace']
        );
    };

    $c['apiErrorHandler'] = function (c $c) {
        return new Handler\ApiError(
            $c->get('logger'), 
            $c->get('settings')['displayErrorDetails'], 
            $c->get('settings')['logTrace']
        );
    };

})($app);
