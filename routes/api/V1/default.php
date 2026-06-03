<?php
    use App\Http\Response;
    use App\Controller\Api;

    $objRouter->get('/api/v1', [
        'middlewares' => [
            'api'
        ],
        function ($request){
            return new Response(200, Api\Api::getDetails($request), 'application/json');
        }
    ]);

    $objRouter->post('/api/v1', [
        function ($request){
            return new Response(200, Api\Api::getDetails($request), 'application/json');
        }
    ]);
?>