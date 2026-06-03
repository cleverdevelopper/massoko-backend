<?php
    use App\Http\Response;
    use App\Controller\Api\V1\Apps\ContactsApiController;

    //================================================
    // Listar Contactos (Protegido)
    //================================================
    $objRouter->get('/api/v1/app/contacts', [
        'middlewares' => [
            'api',
            'jwt-auth'
        ],
        function ($request){
            $result = ContactsApiController::getAppContacts($request);
            return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
        }
    ]);
