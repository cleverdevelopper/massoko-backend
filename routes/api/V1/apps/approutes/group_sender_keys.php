<?php
use App\Http\Response;
use App\Controller\Api\V1\Apps\GroupSenderKeysApiController;

// ============================================================
// Salvar / atualizar Sender Key de grupo (Protegido)
// ============================================================
$objRouter->post('/api/v1/group-sender-keys', [
    'middlewares' => [
        'api',
        'jwt-auth'
    ],
    function ($request) {
        $result = GroupSenderKeysApiController::saveSenderKey($request);
        return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
    }
]);

// ============================================================
// Obter Sender Keys do grupo (Protegido)
// ============================================================
$objRouter->get('/api/v1/group-sender-keys', [
    'middlewares' => [
        'api',
        'jwt-auth'
    ],
    function ($request) {
        $result = GroupSenderKeysApiController::getSenderKeys($request);
        return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
    }
]);
