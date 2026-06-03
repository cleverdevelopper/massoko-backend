<?php
    use App\Http\Response;
    use App\Controller\Api\V1\Apps\MessagesApiController;

    //================================================
    // Enviar Mensagem (Protegido)
    //================================================
    $objRouter->post('/api/v1/messages', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = MessagesApiController::sendMessage($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // Marcar como Lida (Protegido)
    //================================================
    $objRouter->post('/api/v1/messages/read', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = MessagesApiController::markAsRead($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);
    //================================================
    // Listar Mensagens (Protegido)
    //================================================
    $objRouter->get('/api/v1/messages', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = MessagesApiController::getMessages($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // Marcar como Entregue (Protegido)
    //================================================
    $objRouter->post('/api/v1/messages/delivered', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = MessagesApiController::markAsDelivered($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // Listar Mensagens Pendentes (Protegido)
    //================================================
    $objRouter->get('/api/v1/messages/pending', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = MessagesApiController::getPendingMessages($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);
