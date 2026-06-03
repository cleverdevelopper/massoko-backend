<?php

namespace App\Http\Middleware;

class Cors
{
    public static function handle()
    {
        // Domínios permitidos
        $allowedOrigins = [
            'http://localhost:19006',
            'http://localhost:3000',
            'http://192.168.10.73:19006', // Expo em rede local
            'http://192.168.10.73:3000',
            '*' // remover se quiser bloquear mais
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

        // Define headers CORS
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");

        // Responde a preflight requests (obrigatório)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}
