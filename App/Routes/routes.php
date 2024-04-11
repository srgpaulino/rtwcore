<?php
use App\Action;
use Slim\App;

(function (App $app) {

    $root = __DIR__ . '/../';
    $src = $root . 'App/';

    // Test route
    $app->get('/test', Action\Test::class)->setName('test');
    $app->get('/v{version}/test', Action\Test::class)->setName('test');
    
    //callback route
    $app->get('/v{version}/callback', function (\Slim\Http\Request $req, \Slim\Http\Response $res) {
        return $response->write('Route defined in callback rather than separate class.');
    })->setName('callback');

    //version route
    $app->get('/version', Action\Version::class)->setName('version');
    $app->get('/v{version}/version', Action\Version::class)->setName('version');

    //function does not exist. Always keep at the bottom
    $app->get('/v{version}/{function}', Action\NoFunction::class)->setName('nofunction');
    $app->post('/v{version}/{function}', Action\NoFunction::class)->setName('nofunction');
    $app->put('/v{version}/{function}', Action\NoFunction::class)->setName('nofunction');
    $app->patch('/v{version}/{function}', Action\NoFunction::class)->setName('nofunction');
    $app->delete('/v{version}/{function}', Action\NoFunction::class)->setName('nofunction');

    $app->get('/{function}', Action\NoFunction::class)->setName('nofunction');
    $app->post('/{function}', Action\NoFunction::class)->setName('nofunction');
    $app->put('/{function}', Action\NoFunction::class)->setName('nofunction');
    $app->patch('/{function}', Action\NoFunction::class)->setName('nofunction');
    $app->delete('/{function}', Action\NoFunction::class)->setName('nofunction');
    //end of function does not exist

})($app);
