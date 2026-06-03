<?php
    require __DIR__.'/includes/app.php';
    use App\Http\Router;
    use App\Http\Middleware\Cors;

    date_default_timezone_set('Africa/Maputo'); // ou 'UTC' se quiser

    // Ativar CORS
    Cors::handle();

    $objRouter = new Router(URL);
        //inclusao das rotas de api
        include __DIR__.'/routes/routes.php';

    $objRouter->run()
              ->sendResponse();
?>