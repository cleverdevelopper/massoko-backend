<?php

    require __DIR__.'/../vendor/autoload.php';

    
    use App\Utils\ViewManager;
    use App\Common\Environment;
    use App\DatabaseManager\Database;
    use App\Http\Middleware\Queue as MiddlewareQueue;



    Environment::load(__DIR__.'/../');

    Database::config(
        getenv('BD_HOST'),
        getenv('BD_DATABASE'),
        getenv('BD_USERNAME'),
        getenv('BD_PASSWORD'),
        getenv('DB_PORT')
    );

    #=======================================================
    # Variaveis globais
    #=======================================================
    define('URL', getenv('URL'));

    ViewManager::init([
        'URL' => URL
    ]);


    MiddlewareQueue::setMap([
        'maintenance'               => \App\Http\Middleware\Maintenance::class,
        'requere-admin-logout'      => \App\Http\Middleware\RequereUserLogout::class,
        'requere-admin-login'       => \App\Http\Middleware\RequereUserLogin::class,
        'api'                       => \App\Http\Middleware\Api::class,
        'user-basic-auth'           => \App\Http\Middleware\UserBasicAuth::class,
        'jwt-auth'                  => \App\Http\Middleware\JWTAuth::class,
    ]);

    MiddlewareQueue::setDefault([
        'maintenance'
    ]);
    
?>