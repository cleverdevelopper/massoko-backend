<?php
    namespace App\Controller\Api\V1\Apps;
    use App\Controller\Api\Api;
    use App\Model\Entity\Apps\MessagesEntity;
    use App\Model\Entity\Apps\MessageDeliveriesEntity;
    use App\Model\Entity\Apps\ConversationsEntity;
    use App\Model\Entity\Apps\ConversationParticipantsEntity;
    use App\Model\Entity\Apps\PendingDeviceMessagesEntity;
    use App\Model\Entity\Apps\UserDevicesEntity;
    use App\DatabaseManager\Database;
    use Exception;

    class MessagesApiController extends Api {
        
        public static function sendMessage($request) {
            $loggedUser = $request->user;
            if (!$loggedUser) return self::error('Usuário não autenticado', 401);

            $postVars = $request->getPostVars();
            $conversationId = $postVars['conversation_id'] ?? null;
            $content        = $postVars['content'] ?? null;
            $signalType     = $postVars['signal_message_type'] ?? null;
            $type           = $postVars['type'] ?? 'text';
            $replyTo        = $postVars['reply_to_message_id'] ?? null;

            if (!$conversationId || !$content || $signalType === null) {
                return self::error('ID da conversa, conteúdo e tipo de sinal são obrigatórios', 400);
            }

            $db = new Database();
            $queryMembership = "SELECT 1 FROM conversation_participants WHERE conversation_id = :conversationId AND user_id = :authUserId AND deleted_at IS NULL LIMIT 1";
            $statement = $db->execute($queryMembership, ['conversationId' => $conversationId, 'authUserId' => $loggedUser->id]);

            if (!$statement->fetch()) return self::error('Você não tem permissão para enviar mensagens nesta conversa', 403);

            try {
                $db->beginTransaction();

                $obMessage = new MessagesEntity();
                $obMessage->conversation_id   = $conversationId;
                $obMessage->sender_id         = $loggedUser->id;
                $obMessage->encrypted_content = $content;
                $obMessage->signal_message_type = (int)$signalType;
                $obMessage->message_type      = $type;
                $obMessage->reply_to_message_id = $replyTo;
                $obMessage->sent_at           = date('Y-m-d H:i:s');
                $messageId = $obMessage->cadastrar();

                $obConversation = ConversationsEntity::getConversationById($conversationId);
                if ($obConversation) {
                    $obConversation->last_message_id = $messageId;
                    $obConversation->last_message_at = date('Y-m-d H:i:s');
                    $obConversation->actualizar();
                }

                // Colocar em PendingDeviceMessages para todos os dispositivos dos outros participantes
                $queryDevices = "
                    SELECT ud.id as user_device_id 
                    FROM conversation_participants cp
                    JOIN user_devices ud ON cp.user_id = ud.user_id
                    WHERE cp.conversation_id = :convId 
                      AND cp.user_id != :senderId
                      AND cp.deleted_at IS NULL
                      AND ud.is_active = 1
                ";
                $devStatement = $db->execute($queryDevices, ['convId' => $conversationId, 'senderId' => $loggedUser->id]);
                while ($row = $devStatement->fetch(\PDO::FETCH_ASSOC)) {
                    $pending = new PendingDeviceMessagesEntity();
                    $pending->message_id = $messageId;
                    $pending->user_device_id = $row['user_device_id'];
                    $pending->cadastrar();
                }

                $db->commit();

                return [
                    'id'                  => (int)$messageId,
                    'conversation_id'     => (int)$conversationId,
                    'sender_id'           => (int)$loggedUser->id,
                    'encrypted_content'   => $content,
                    'signal_message_type' => (int)$signalType,
                    'type'                => $type,
                    'sent_at'             => $obMessage->sent_at
                ];

            } catch (Exception $e) {
                $db->rollBack();
                return self::error('Falha ao enviar mensagem: ' . $e->getMessage(), 500);
            }
        }

        public static function markAsDelivered($request) {
            $loggedUser = $request->user;
            if (!$loggedUser) return self::error('Usuário não autenticado', 401);

            $postVars = $request->getPostVars();
            $messageId = $postVars['message_id'] ?? null;
            $deviceId = $postVars['device_id'] ?? null;

            if (!$messageId || !$deviceId) return self::error('message_id e device_id obrigatórios', 400);

            $device = UserDevicesEntity::getDeviceByUserIdAndDeviceId($loggedUser->id, $deviceId);
            if (!$device) return self::error('Dispositivo não encontrado', 404);

            $delivery = new MessageDeliveriesEntity();
            $delivery->message_id = $messageId;
            $delivery->user_device_id = $device->id;
            $delivery->cadastrar();
            $delivery->marcarComoEntregue();

            // Retirar do pending
            $db = new Database();
            $db->execute("DELETE FROM pending_device_messages WHERE message_id = :mid AND user_device_id = :udid", [
                'mid' => $messageId,
                'udid' => $device->id
            ]);

            return self::success('Mensagem marcada como entregue', [], 200);
        }

        public static function markAsRead($request) {
            $loggedUser = $request->user;
            if (!$loggedUser) return self::error('Usuário não autenticado', 401);

            $postVars = $request->getPostVars();
            $conversationId = $postVars['conversation_id'] ?? null;
            $deviceId = $postVars['device_id'] ?? null;

            if (!$conversationId || !$deviceId) return self::error('conversation_id e device_id são obrigatórios', 400);

            $device = UserDevicesEntity::getDeviceByUserIdAndDeviceId($loggedUser->id, $deviceId);
            if (!$device) return self::error('Dispositivo não encontrado', 404);

            $db = new Database();
            try {
                $db->beginTransaction();

                $queryUnread = "
                    SELECT m.id 
                    FROM messages m
                    LEFT JOIN message_deliveries md ON md.message_id = m.id AND md.user_device_id = :deviceDbId
                    WHERE m.conversation_id = :convId
                      AND m.sender_id != :authUserId
                      AND (md.read_at IS NULL)
                ";
                
                $statement = $db->execute($queryUnread, [
                    'convId' => $conversationId,
                    'authUserId' => $loggedUser->id,
                    'deviceDbId' => $device->id
                ]);

                while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                    // Try to update existing delivery or create one
                    $existing = MessageDeliveriesEntity::getDeliveries('message_id = ' . $row['id'] . ' AND user_device_id = ' . $device->id)->fetchObject(MessageDeliveriesEntity::class);
                    if ($existing) {
                        $existing->marcarComoLida();
                    } else {
                        $deliv = new MessageDeliveriesEntity();
                        $deliv->message_id = $row['id'];
                        $deliv->user_device_id = $device->id;
                        $deliv->cadastrar();
                        $deliv->marcarComoLida();
                    }
                    
                    // Cleanup pending just in case
                    $db->execute("DELETE FROM pending_device_messages WHERE message_id = :mid AND user_device_id = :udid", [
                        'mid' => $row['id'],
                        'udid' => $device->id
                    ]);
                }

                $db->commit();
                return ['conversation_id' => (int)$conversationId, 'status' => 'read'];

            } catch (Exception $e) {
                $db->rollBack();
                return self::error('Falha ao marcar como lida: ' . $e->getMessage(), 500);
            }
        }

        public static function getPendingMessages($request) {
            $loggedUser = $request->user;
            if (!$loggedUser) return self::error('Usuário não autenticado', 401);

            $queryParams = $request->getQueryParams();
            $deviceId = $queryParams['device_id'] ?? null;

            if (!$deviceId) return self::error('device_id obrigatório', 400);

            $device = UserDevicesEntity::getDeviceByUserIdAndDeviceId($loggedUser->id, $deviceId);
            if (!$device) return self::error('Dispositivo não encontrado', 404);

            $pending = PendingDeviceMessagesEntity::getPendingMessages($device->id);
            $messages = [];
            while ($p = $pending->fetchObject(PendingDeviceMessagesEntity::class)) {
                $m = MessagesEntity::getMessageById($p->message_id);
                if ($m) {
                    $messages[] = [
                        'id' => (int)$m->id,
                        'conversation_id' => (int)$m->conversation_id,
                        'sender_id' => (int)$m->sender_id,
                        'encrypted_content' => $m->encrypted_content,
                        'signal_message_type' => (int)$m->signal_message_type,
                        'type' => $m->message_type,
                        'sent_at' => $m->sent_at
                    ];
                }
            }

            return self::success('Mensagens pendentes', ['messages' => $messages], 200);
        }

        public static function getMessages($request) {
            $loggedUser = $request->user;
            if (!$loggedUser) return self::error('Usuário não autenticado', 401);

            $queryParams = $request->getQueryParams();
            $conversationId = $queryParams['conversation_id'] ?? null;

            if (!$conversationId) return self::error('O ID da conversa é obrigatório', 400);

            $db = new Database();
            $queryMembership = "SELECT 1 FROM conversation_participants WHERE conversation_id = :convId AND user_id = :authUserId AND deleted_at IS NULL LIMIT 1";
            $statement = $db->execute($queryMembership, ['convId' => $conversationId, 'authUserId' => $loggedUser->id]);

            if (!$statement->fetch()) return self::error('Você não tem acesso a esta conversa', 403);

            $results = MessagesEntity::getMessages('conversation_id = ' . $conversationId, 'sent_at ASC');
            
            $messages = [];
            while ($obMessage = $results->fetchObject(MessagesEntity::class)) {
                $messages[] = [
                    'id'                  => (int)$obMessage->id,
                    // Both aliases kept for backward compatibility with older clients
                    'text'                => $obMessage->encrypted_content,
                    'encrypted_content'   => $obMessage->encrypted_content,
                    'signal_message_type' => (int)$obMessage->signal_message_type,
                    'sender'              => $obMessage->sender_id == $loggedUser->id ? 'me' : 'other',
                    'sender_id'           => (int)$obMessage->sender_id,
                    'sent_at'             => $obMessage->sent_at,
                    'time'                => date('H:i', strtotime($obMessage->sent_at)),
                    'type'                => $obMessage->message_type,
                    'status'              => 'sent'
                ];
            }

            return [
                'success'  => true,
                'messages' => array_reverse($messages)
            ];
        }
    }
?>
