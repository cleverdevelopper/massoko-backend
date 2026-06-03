<?php
namespace App\Controller\Api\V1\Apps;

use App\Controller\Api\Api;
use App\Model\Entity\Apps\GroupSenderKeysEntity;
use App\Model\Entity\Apps\UserDevicesEntity;
use App\DatabaseManager\Database;

class GroupSenderKeysApiController extends Api {

    // ============================================================
    // POST /api/v1/group-sender-keys
    // Salva ou atualiza a chave de remetente do grupo (Sender Key)
    // ============================================================
    public static function saveSenderKey($request) {
        $loggedUser = $request->user;
        if (!$loggedUser) return self::error('Usuário não autenticado', 401);

        $postVars = $request->getPostVars();
        $conversationId = $postVars['conversation_id'] ?? null;
        $userDeviceId   = $postVars['user_device_id'] ?? null;
        $senderKey      = $postVars['sender_key'] ?? null;

        if (!$conversationId || !$userDeviceId || !$senderKey) {
            return self::error('conversation_id, user_device_id e sender_key são obrigatórios', 400);
        }

        // Verificar se o dispositivo pertence ao usuário (ou se é o remetente válido)
        $device = UserDevicesEntity::getDeviceById($userDeviceId);
        if (!$device) {
            return self::error('Dispositivo não encontrado', 404);
        }

        // Upsert no banco de dados
        $db = new Database('group_sender_keys');
        $existing = $db->select('conversation_id = ' . (int)$conversationId . ' AND user_device_id = ' . (int)$userDeviceId)->fetchObject(GroupSenderKeysEntity::class);

        if ($existing) {
            $db->update('id = ' . $existing->id, [
                'sender_key' => $senderKey
            ]);
            $id = $existing->id;
        } else {
            $obKey = new GroupSenderKeysEntity();
            $obKey->conversation_id = $conversationId;
            $obKey->user_device_id  = $userDeviceId;
            $obKey->sender_key      = $senderKey;
            $id = $obKey->cadastrar();
        }

        return self::success('Chave de remetente do grupo salva com sucesso', ['id' => (int)$id], 200);
    }

    // ============================================================
    // GET /api/v1/group-sender-keys
    // Recupera chaves de remetente para participantes do grupo
    // ============================================================
    public static function getSenderKeys($request) {
        $loggedUser = $request->user;
        if (!$loggedUser) return self::error('Usuário não autenticado', 401);

        $queryParams = $request->getQueryParams();
        $conversationId = $queryParams['conversation_id'] ?? null;
        $userDeviceId   = $queryParams['user_device_id'] ?? null;

        if (!$conversationId) {
            return self::error('conversation_id é obrigatório', 400);
        }

        $where = 'conversation_id = ' . (int)$conversationId;
        if ($userDeviceId) {
            $where .= ' AND user_device_id = ' . (int)$userDeviceId;
        }

        $results = GroupSenderKeysEntity::getKeys($where);
        $keys = [];
        while ($key = $results->fetchObject(GroupSenderKeysEntity::class)) {
            $keys[] = [
                'id'              => (int)$key->id,
                'conversation_id' => (int)$key->conversation_id,
                'user_device_id'  => (int)$key->user_device_id,
                'sender_key'      => $key->sender_key,
                'created_at'      => $key->created_at
            ];
        }

        return self::success('Chaves de remetente do grupo recuperadas', ['sender_keys' => $keys], 200);
    }
}
