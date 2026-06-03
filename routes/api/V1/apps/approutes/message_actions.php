<?php
use App\Http\Response;
use App\Controller\Api\V1\Apps\MessageActionsApiController;

// ============================================================
// ATTACHMENTS (Protegido)
// ============================================================
$objRouter->post('/api/v1/messages/attachments', [
    'middlewares' => [
        'api',
        'jwt-auth'
    ],
    function ($request) {
        $result = MessageActionsApiController::addAttachment($request);
        return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
    }
]);

$objRouter->get('/api/v1/messages/{id}/attachments', [
    'middlewares' => [
        'api',
        'jwt-auth'
    ],
    function ($request, $id) {
        $result = MessageActionsApiController::getAttachments($request, $id);
        return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
    }
]);

// ============================================================
// REACTIONS (Protegido)
// ============================================================
$objRouter->post('/api/v1/messages/{id}/reactions', [
    'middlewares' => [
        'api',
        'jwt-auth'
    ],
    function ($request, $id) {
        $result = MessageActionsApiController::addReaction($request, $id);
        return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
    }
]);

$objRouter->delete('/api/v1/messages/{id}/reactions', [
    'middlewares' => [
        'api',
        'jwt-auth'
    ],
    function ($request, $id) {
        $result = MessageActionsApiController::removeReaction($request, $id);
        return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
    }
]);

$objRouter->get('/api/v1/messages/{id}/reactions', [
    'middlewares' => [
        'api',
        'jwt-auth'
    ],
    function ($request, $id) {
        $result = MessageActionsApiController::getReactions($request, $id);
        return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
    }
]);

// ============================================================
// EDITS (Protegido)
// ============================================================
$objRouter->post('/api/v1/messages/{id}/edit', [
    'middlewares' => [
        'api',
        'jwt-auth'
    ],
    function ($request, $id) {
        $result = MessageActionsApiController::editMessage($request, $id);
        return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
    }
]);

$objRouter->get('/api/v1/messages/{id}/edits', [
    'middlewares' => [
        'api',
        'jwt-auth'
    ],
    function ($request, $id) {
        $result = MessageActionsApiController::getEditHistory($request, $id);
        return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
    }
]);

// ============================================================
// DELETE FOR EVERYONE (Protegido)
// ============================================================
$objRouter->post('/api/v1/messages/{id}/delete-everyone', [
    'middlewares' => [
        'api',
        'jwt-auth'
    ],
    function ($request, $id) {
        $result = MessageActionsApiController::deleteMessageForEveryone($request, $id);
        return $result instanceof Response ? $result : new Response(200, $result, 'application/json');
    }
]);
