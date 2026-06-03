<?php
    use App\Http\Response;
    use App\Controller\Api\V1\Apps\KeysApiController;

    //================================================
    // POST /api/v1/keys
    // Upload das chaves principais do utilizador
    //================================================
    $objRouter->post('/api/v1/keys', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request) {
            $result = KeysApiController::uploadKeys($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // GET /api/v1/users/{id}/keys
    // Obter bundle de chaves para iniciar sessão E2E
    //================================================
    $objRouter->get('/api/v1/users/{id}/keys', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request, $id) {
            $result = KeysApiController::getKeyBundle($request, $id);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // POST /api/v1/keys/prekeys
    // Upload de prekeys adicionais
    //================================================
    $objRouter->post('/api/v1/keys/prekeys', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request) {
            $result = KeysApiController::uploadMorePrekeys($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);
    //================================================
    // GET /api/v1/keys/status
    // Verificar estado das chaves do utilizador logado
    //================================================
    $objRouter->get('/api/v1/keys/status', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request) {
            $result = KeysApiController::getKeyStatus($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);
?>
