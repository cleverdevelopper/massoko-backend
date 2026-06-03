<?php

namespace App\Model\Entity\Apps;

use App\DatabaseManager\Database;

class RefreshTokensEntity
{
    public $id;
    public $user_id;
    public $token;
    public $expires_at;
    public $revoked;
    public $device_info;
    public $created_at;

    public function cadastrar()
    {
        $this->created_at = date('Y-m-d H:i:s');
        $this->id = (new Database('refresh_tokens'))->insert([
            'user_id'     => $this->user_id,
            'token'       => $this->token,
            'expires_at'  => $this->expires_at,
            'revoked'     => $this->revoked ?? 0,
            'device_info' => $this->device_info,
            'created_at'  => $this->created_at
        ]);

        return $this->id;
    }

    public static function getRefreshTokens($where = null, $order = null, $limit = null, $fields = "*")
    {
        return (new Database('refresh_tokens'))->select($where, $order, $limit, $fields);
    }

    public static function getRefreshTokenByToken($token)
    {
        return self::getRefreshTokens('token = "' . $token . '"')->fetchObject(self::class);
    }


    public static function getRefreshTokenByUserId($userId)
    {
        return self::getRefreshTokens('user_id = ' . $userId, 'id DESC', '1')->fetchObject(self::class);
    }


    public static function getRefreshTokenById($id)
    {
        return self::getRefreshTokens('id = ' . $id)->fetchObject(self::class);
    }

    public function actualizar()
    {
        return (new Database('refresh_tokens'))->update('id = ' . $this->id, [
            'user_id'     => $this->user_id,
            'token'       => $this->token,
            'expires_at'  => $this->expires_at,
            'revoked'     => $this->revoked,
            'device_info' => $this->device_info,
            'created_at'  => $this->created_at
        ]);
    }
}
