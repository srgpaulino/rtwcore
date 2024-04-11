<?php
(function(){
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $root = __DIR__ . '/../';
    $src = $root . 'App/';

    session_start();

    require_once $src . 'functions.php';

    // Dotenv complains with trailing slash on directory
    $dotenv = new Dotenv\Dotenv(substr($root, 0, -1));
    $dotenv->load();

    // Setting global timezone
    date_default_timezone_set(env('TIMEZONE', 'Europe/London'));

    $app = new Slim\App(require_once $src . 'settings.php');

    require_once $src . 'settings.php';
    require_once $src . 'handlers.php';
    require_once $src . 'dependencies.php';
    require_once $src . 'actions.php';
    require_once $src . 'middleware.php';

    foreach(glob($src . 'Routes/*.php') as $file) {
        require_once $file;    
    }

    $app->run();
})();
