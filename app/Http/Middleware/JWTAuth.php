<?php

namespace App\Http\Middleware;

use App\Controller\Api\Api;
use App\Model\Entity\Apps\UsersEntity;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTAuth
{
   
    public function handle($request, $next)
    {
        try {
            // Obtém os headers
            $headers = $request->getHeaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

            // Valida a presença do token
            if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                throw new Exception('Token de acesso não fornecido ou formato inválido', 401);
            }

            $token = $matches[1];
            $jwtSecret = getenv('JWT_SECRET') ?: 'massoko_secret_key_2024';

            // Decodifica o token
            $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));
            $userId = $decoded->user_id ?? null;

            if (!$userId) {
                throw new Exception('Token inválido: ID do usuário ausente', 401);
            }

            // Verifica se o usuário existe
            $obUser = UsersEntity::getUserById($userId);
            if (!$obUser) {
                throw new Exception('Usuário associado ao token não encontrado', 404);
            }

            // Passa o usuário autenticado para o request (se o objeto Request tiver suporte ou via variável global)
            // Como o Request é uma classe simples, podemos usar o registro para manter o estado do usuário
            $request->user = $obUser;

            // Executa o próximo nível
            return $next($request);

        } catch (Exception $e) {
            // Em caso de erro, retorna uma resposta JSON 401/404
            $message = $e->getMessage();
            
            // Tratamento específico para erros do Firebase JWT
            if (strpos($message, 'Expired token') !== false) {
                $message = 'Token de acesso expirado';
            } else if (strpos($message, 'Signature verification failed') !== false) {
                $message = 'Falha na verificação da assinatura do token';
            }

            return Api::error($message, $e->getCode() == 0 ? 401 : $e->getCode());
        }
    }
}
