<?php
    use App\Http\Response;
    use App\Controller\Api\V1\Apps\ConversationsApiController;

    //================================================
    // Obter ou Criar Conversa Privada (Protegido)
    //================================================
    $objRouter->post('/api/v1/conversations/private', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = ConversationsApiController::getOrCreatePrivateConversation($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);
    //================================================
    // Listar Conversas do Usuário (Protegido)
    //================================================
    $objRouter->get('/api/v1/conversations', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = ConversationsApiController::getConversations($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);
