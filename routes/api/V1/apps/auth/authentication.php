<?php
    use App\Http\Response;
    use App\Controller\Api\V1\Apps\AuthenticationApiController;
    
    $objRouter->post('/api/v1/auth/register-phone-number', [
        'middlewares' => [
            'api'
        ],
        function ($request){
            $result = AuthenticationApiController::phoneNumberRgister($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // Verificar OTP
    //================================================
    $objRouter->post('/api/v1/auth/verify-otp', [
        'middlewares' => [
            'api'
        ],
        function ($request){
            $result = AuthenticationApiController::verifyOtp($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // Finalizar Registro / Login Sucesso
    //================================================
    $objRouter->post('/api/v1/auth/finalize-registration', [
        'middlewares' => [
            'api'
        ],
        function ($request){
            $result = AuthenticationApiController::finalizeRegistration($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    $objRouter->post('/api/v1/auth/login-success', [
        'middlewares' => [
            'api'
        ],
        function ($request){
            $result = AuthenticationApiController::finalizeRegistration($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // Renovar Token (Refresh)
    //================================================
    $objRouter->post('/api/v1/auth/refresh-token', [
        'middlewares' => [
            'api'
        ],
        function ($request){
            $result = AuthenticationApiController::refreshToken($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    $objRouter->post('/api/v1/auth/refresh', [
        'middlewares' => [
            'api'
        ],
        function ($request){
            $result = AuthenticationApiController::refreshToken($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // Obter Dados do Usuário (Protegido)
    //================================================
    $objRouter->get('/api/v1/auth/me', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = AuthenticationApiController::getMe($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // Deslogar (Protegido)
    //================================================
    $objRouter->post('/api/v1/auth/logout', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = AuthenticationApiController::logout($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // Perfil do Usuário (Protegido)
    //================================================
    $objRouter->get('/api/v1/auth/profile', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = AuthenticationApiController::getUserProfile($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    //================================================
    // Actualizar Perfil / Avatar (Protegido)
    //================================================
    $objRouter->post('/api/v1/auth/update-profile', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = AuthenticationApiController::updateProfile($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);

    // Alias kept for older clients that call /update-avatar
    $objRouter->post('/api/v1/auth/update-avatar', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = AuthenticationApiController::updateAvatar($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);