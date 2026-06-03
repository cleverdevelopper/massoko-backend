<?php
    namespace App\Controller\Api\V1\Apps;
    use App\Controller\Api\Api;
    use App\Model\Entity\Apps\UsersEntity;
    use App\Model\Entity\Apps\ConversationsEntity;
    use App\Model\Entity\Apps\ConversationParticipantsEntity;
    use App\DatabaseManager\Database;
    use Exception;

    class ConversationsApiController extends Api {
        
        /**
         * Método responsável por retornar ou criar uma conversa privada entre dois usuários
         * @param Request $request
         * @return array
         */
        public static function getOrCreatePrivateConversation($request) {
            // 1. Validar JWT e obter usuário autenticado (já injetado pelo middleware jwt-auth)
            $loggedUser = $request->user;
            if (!$loggedUser) {
                return self::error('Usuário não autenticado', 401);
            }

            // 2. Obter dados do request
            $postVars     = $request->getPostVars();
            $targetUserId = $postVars['user_id'] ?? null;

            // 3. Validações básicas
            if (!$targetUserId) {
                return self::error('O ID do usuário de destino é obrigatório', 400);
            }

            if ($loggedUser->id == $targetUserId) {
                return self::error('Você não pode criar uma conversa consigo mesmo', 400);
            }

            // 4. Verificar se o usuário de destino existe
            $targetUser = UsersEntity::getUserById($targetUserId);
            if (!$targetUser) {
                return self::error('Usuário de destino não encontrado', 404);
            }

            // 5. Verificar se a conversa já existe (conforme lógica solicitada)
            $db = new Database();
            $query = "SELECT c.id 
                      FROM conversations c
                      JOIN conversation_participants p1 ON p1.conversation_id = c.id
                      JOIN conversation_participants p2 ON p2.conversation_id = c.id
                      WHERE c.type = 'private'
                      AND p1.user_id = :authUserId
                      AND p2.user_id = :targetUserId
                      AND p1.deleted_at IS NULL
                      AND p2.deleted_at IS NULL
                      LIMIT 1";
            
            $statement = $db->execute($query, [
                'authUserId'   => $loggedUser->id,
                'targetUserId' => $targetUserId
            ]);
            
            $existing = $statement->fetchObject();

            if ($existing) {
                return [
                    'conversation_id' => (int)$existing->id,
                    'created'         => false
                ];
            }

            // 6. Criar nova conversa usando transação
            try {
                // Obter a conexão PDO para gerenciar a transação manualmente se necessário, 
                // ou usar os métodos da classe Database
                $db->beginTransaction();

                // Passo 1: Criar a conversa
                $obConversation = new ConversationsEntity();
                $obConversation->type       = 'private';
                $obConversation->created_by = $loggedUser->id;
                $conversationId = $obConversation->cadastrar();

                if (!$conversationId) {
                    throw new Exception('Falha ao inserir registro na tabela de conversas');
                }

                // Passo 2: Adicionar participantes
                // Participante 1 (Usuário Autenticado)
                $obPart1 = new ConversationParticipantsEntity();
                $obPart1->conversation_id = $conversationId;
                $obPart1->user_id         = $loggedUser->id;
                $obPart1->joined_at       = date('Y-m-d H:i:s');
                $obPart1->cadastrar();

                // Participante 2 (Usuário de Destino)
                $obPart2 = new ConversationParticipantsEntity();
                $obPart2->conversation_id = $conversationId;
                $obPart2->user_id         = $targetUserId;
                $obPart2->joined_at       = date('Y-m-d H:i:s');
                $obPart2->cadastrar();

                $db->commit();

                return [
                    'conversation_id' => (int)$conversationId,
                    'created'         => true
                ];

            } catch (Exception $e) {
                $db->rollBack();
                return self::error('Falha ao processar a criação da conversa: ' . $e->getMessage(), 500);
            }
        }

        /**
         * Método responsável por listar as conversas do usuário autenticado
         * @param Request $request
         * @return array
         */
        public static function getConversations($request) {
            $loggedUser = $request->user;
            if (!$loggedUser) {
                return self::error('Usuário não autenticado', 401);
            }

            $db = new Database();
            
            // Query para buscar as conversas e os dados do outro participante
            $query = "SELECT 
                        c.id as conversation_id,
                        c.type,
                        c.last_message_at,
                        m.encrypted_content as last_message,
                        m.sent_at as last_message_time,
                        m.sender_id as last_message_sender_id,
                        u.name as other_user_name,
                        u.surname as other_user_surname,
                        up.profile_photo as other_user_photo,
                        u.id as other_user_id,
                        u.phone_number as other_user_phone,
                        m.sender_id as last_message_sender_id,
                        CASE 
                            WHEN m.sender_id IS NULL THEN NULL
                            WHEN EXISTS (
                                SELECT 1 FROM message_deliveries md 
                                JOIN user_devices ud ON md.user_device_id = ud.id 
                                WHERE md.message_id = m.id 
                                  AND ud.user_id = u.id 
                                  AND md.read_at IS NOT NULL
                            ) THEN 'read'
                            WHEN EXISTS (
                                SELECT 1 FROM message_deliveries md 
                                JOIN user_devices ud ON md.user_device_id = ud.id 
                                WHERE md.message_id = m.id 
                                  AND ud.user_id = u.id 
                                  AND md.delivered_at IS NOT NULL
                            ) THEN 'delivered'
                            ELSE 'sent'
                        END as last_message_status,
                        (SELECT COUNT(*) FROM messages msg 
                         WHERE msg.conversation_id = c.id 
                           AND msg.sender_id != :authUserId 
                           AND msg.deleted_at IS NULL
                           AND NOT EXISTS (
                               SELECT 1 FROM message_deliveries md 
                               JOIN user_devices ud ON md.user_device_id = ud.id 
                               WHERE md.message_id = msg.id 
                                 AND ud.user_id = :authUserId 
                                 AND md.read_at IS NOT NULL
                           )
                        ) as unread_count
                      FROM conversations c
                      JOIN conversation_participants p ON p.conversation_id = c.id
                      LEFT JOIN messages m ON m.id = c.last_message_id
                      JOIN conversation_participants p2 ON p2.conversation_id = c.id AND p2.user_id != :authUserId
                      JOIN users u ON u.id = p2.user_id
                      LEFT JOIN user_profiles up ON u.id = up.user_id
                      WHERE p.user_id = :authUserId
                        AND p.deleted_at IS NULL
                      ORDER BY c.last_message_at DESC, c.created_at DESC";

            $statement = $db->execute($query, ['authUserId' => $loggedUser->id]);
            
            $conversations = [];
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $conversations[] = [
                    'id'             => (int)$row['conversation_id'],
                    'type'           => $row['type'],
                    'name'           => $row['other_user_name'] . ($row['other_user_surname'] ? ' ' . $row['other_user_surname'] : ''),
                    'image'          => self::getProfilePhotoUrl($row['other_user_photo']),
                    'last_message'   => $row['last_message'] ?? 'Inicie uma conversa...',
                    'time'           => $row['last_message_time'] ? date('H:i', strtotime($row['last_message_time'])) : '',
                    'unread'         => (int)$row['unread_count'],
                    'other_user_id'  => (int)$row['other_user_id'],
                    'phone'          => $row['other_user_phone'],
                    'last_message_sender_id' => (int)$row['last_message_sender_id'],
                    'last_message_status'    => $row['last_message_status']
                ];
            }

            return [
                'success'       => true,
                'conversations' => $conversations
            ];
        }

        /**
         * Método responsável por retornar a URL completa da foto de perfil
         * @param string $photo
         * @return string
         */
        private static function getProfilePhotoUrl($photo) {
            if (empty($photo)) {
                return null;
            }
            return getenv('URL') . '/images/avatars/' . $photo;
        }
    }
