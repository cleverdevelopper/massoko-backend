<?php
    use App\Http\Response;
    use App\Controller\Api\V1\Apps\SignalApiController;

    //================================================
    // Registar Dispositivo (Protegido)
    //================================================
    $objRouter->post('/api/v1/signal/register-device', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = SignalApiController::registerDevice($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // Salvar Sessão Signal (Protegido)
    //================================================
    $objRouter->post('/api/v1/signal/session/save', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = SignalApiController::saveSession($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // Carregar Sessão Signal (Protegido)
    //================================================
    $objRouter->get('/api/v1/signal/session/load', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = SignalApiController::loadSession($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // Resetar Sessões Signal (Protegido)
    //================================================
    $objRouter->delete('/api/v1/signal/session/reset', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = SignalApiController::resetSessions($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // Rodar Signed PreKey (Protegido)
    //================================================
    $objRouter->post('/api/v1/signal/rotate-signed-prekey', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = SignalApiController::rotateSignedPrekey($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);
?>
