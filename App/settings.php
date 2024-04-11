<?php

return [
    'settings' => [
        // Slim
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => (int)env('ERROR_DETAILS', 0),
        'logTrace' => (int)env('ERROR_LOG_TRACE', 1),
        'timezone' => (string)env('TIMEZONE', 'Europe/London'),
        //'routerCacheFile' => __DIR__ . '/routes.cache.php',

        // Environment - local, staging, production
        'environment' => (string)env('APP_ENV', 'local'),
        'version'     => (string)env('APP_VER', '1.0'),
        'author'     => (string)env('APP_AUTHOR', 'TFC International'),

        // Database
        'db' => [
            'admin' => [
                'host'     => (string)env('DB_HOST_ADMIN'),
                'port'     => (int)env('DB_PORT_ADMIN'),
                'user'     => (string)env('DB_USER_ADMIN'),
                'pass'     => (string)env('DB_PASS_ADMIN'),
                'database' => (string)env('DB_DATABASE_ADMIN'),
                'opts'     => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ],
                ],
            'tfc' => [
                    'host'     => (string)env('DB_HOST_TFC'),
                    'port'     => (int)env('DB_PORT_TFC'),
                    'user'     => (string)env('DB_USER_TFC'),
                    'pass'     => (string)env('DB_PASS_TFC'),
                    'database' => (string)env('DB_DATABASE_TFC'),
                    'opts'     => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    ],
                ],
            'log' => [
                'host'     => (string)env('DB_HOST_LOG'),
                'port'     => (int)env('DB_PORT_LOG'),
                'user'     => (string)env('DB_USER_LOG'),
                'pass'     => (string)env('DB_PASS_LOG'),
                'database' => (string)env('DB_DATABASE_LOG'),
                'opts'     => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ],
            ],
            'tfc' => [
                'host'     => (string)env('DB_HOST_TFC'),
                'port'     => (int)env('DB_PORT_TFC'),
                'user'     => (string)env('DB_USER_TFC'),
                'pass'     => (string)env('DB_PASS_TFC'),
                'database' => (string)env('DB_DATABASE_TFC'),
                'opts'     => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ],
            ]
        ],

        //Salesforce
        'salesforce' => [
            'access' => [
                'grant_type' => 'password',
                'client_id' => (string)env('SF_CONSUMER_KEY', 'conskey'),
                'client_secret' => (string)env('SF_CONSUMER_SECRET', 'shhhh'),
                'username' => (string)env('SF_USER', 'user'),
                'password' => (string)env('SF_PASSWORD', 'password_123') . (string)env('SF_TOKEN', 'token123')
            ],
            'logger' => [
                'name'  => 'SFlog',
                'path'  => __DIR__ . '/../log/'. $_SERVER['SERVER_NAME'] . '.sf.' . (new DateTimeImmutable("now", new DateTimeZone("UTC")))->format("Y-m-d").".log",
                'level' => (int)env('LOGGER_LEVEL', 200), // See Monolog\Logger constants
            ]
        ],
        
        //Cognito
        'cognito' => [
            'credentials'   => [
                'key'       => (string)env('AWS_ACCESS_KEY_ID'),
                'secret'    => (string)env('AWS_SECRET_ACCESS_KEY'),
            ],
            'region'            => (string)env('AWS_REGION'),
            'version'           => (string)env('AWS_VERSION'),
            'appClientId'     => (string)env('AWS_COGNITO_APPCLIENTID'),
            'appClientSecret' => (string)env('AWS_COGNITO_SECRET'),
            'userPoolId'      => (string)env('AWS_COGNITO_IDENTITYPOOLID'),
        ],

        //Enable CORS
        'corsOptions' => [
            'origin'            => '*',
            'allowCredentials'  => true,
            'allowMethods'      => ['POST, GET, PUT, DELETE'],
            'allowHeaders'      => ['Origin'],
            'P3P'               => ['CP="CAO PSA OUR"']
        ],

        //HTTP Auth
        /*'HttpBasicAuthentication' => [
            'path'              => ['/v2'],
            'realm'             => 'Protected',
            'secure'            => false,
            "authenticator" => new PdoAuthenticator([
                'pdo'      => new \Slim\PDO\Database("mysql:host=".(string)env('DB_HOST_MAIN', 'localhost').";dbname=".(string)env('DB_DATABASE_MAIN', 'tfcapidb').";charset=utf8", (string)env('DB_USER_MAIN', 'tfcapi'), (string)env('DB_PASS_MAIN', 'tfc2017api')),
                'table'    => 'user',
                'user'     => 'username',
                'pass'     => 'hash',

            ]),
            "error" => function ($request, $response, $arguments) {
                $data = [];
                $data["error"] = "ERR401";
                $data["message"] = $arguments["message"];
                return $response->write(json_encode($data, JSON_UNESCAPED_SLASHES));
            }
        ],*/

        // Cache
        'cache' => [
            'name'  => 'public',
            'time'  =>  86400
        ],

        // Logging
        'logger' => [
            'name'  => 'api',
            'path'  => __DIR__ . '/../log/'.
                $_SERVER['SERVER_NAME'] . '.' .
                (new DateTimeImmutable("now", new DateTimeZone("UTC")))->format("Y-m-d").
                ".log",
            'level' => (int)env('LOGGER_LEVEL', 100), // See Monolog\Logger constants
        ],

        'providers' => [
            'nexway' => [
                'realmName' => (string)env('NEXWAY_REALM_NAME'),
                'clientSecret' => (string)env('NEXWAY_CLIENT_SECRET'),
                'auth' => [
                    'GB' => (string)env('NEXWAY_AUTH_GB'),
                    'DE' => (string)env('NEXWAY_AUTH_DE'),
                    'ES' => (string)env('NEXWAY_AUTH_ES'),
                    'FR' => (string)env('NEXWAY_AUTH_FR'),
                    'IT' => (string)env('NEXWAY_AUTH_IT'),
                    'EU' => (string)env('NEXWAY_AUTH_EU'),
                    'US' => (string)env('NEXWAY_AUTH_US'),
                ]
            ],
            'zinio' => [
                
            ]
        ]
    ]
];
