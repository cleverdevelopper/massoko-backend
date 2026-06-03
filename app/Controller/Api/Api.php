<?php

namespace App\Controller\Api;

use App\Http\Response;

class Api
{
    public static function getDetails($request)
    {
        return [
            'name'      => 'Massoko - API',
            'versao'    => 'v1.0.0',
            'autor'     => 'Clever Developer',
            'company'   => 'Massoko - Plataforma de troca de mensagens instantaneas'
        ];
    }

    protected static function getPagination($request, $obPagination)
    {
        $query_params = $request->getQueryParams();

        $pages = $obPagination->getPages();

        return [
            'paginaActual'       => isset($query_params['page']) ?  (int)$query_params['page'] : 1,
            'quantidadePaginas'  => !empty($pages) ? count($pages) : 1
        ];
    }



    /**
     * Resposta de sucesso
     */
    public static function success(
        string $status = 'Operação realizada com sucesso',
        array $data = [],
        int $httpCode = 200
    ) {
        return new Response(
            $httpCode,
            array_merge([
                'success' => true,
                'status'  => $status
            ], $data),
            'application/json'
        );
    }

    /**
     * Resposta de erro
     */
    public static function error(
        string $message,
        int $httpCode = 400,
        array $extra = []
    ) {
        return new Response(
            $httpCode,
            array_merge([
                'success' => false,
                'message' => $message
            ], $extra),
            'application/json'
        );
    }
}
