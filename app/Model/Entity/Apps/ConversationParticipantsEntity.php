<?php
    namespace App\Model\Entity\Apps;
    use App\DatabaseManager\Database;

    class ConversationParticipantsEntity {
        public $id;
        public $conversation_id;
        public $user_id;
        public $role;
        public $last_read_message_id;
        public $joined_at;
        public $created_at;
        public $updated_at;
        public $deleted_at;

        public function cadastrar() {
            $this->id = (new Database('conversation_participants'))->insert([
                'conversation_id'      => $this->conversation_id,
                'user_id'              => $this->user_id,
                'role'                 => $this->role ?? 'member',
                'last_read_message_id' => $this->last_read_message_id,
                'joined_at'            => $this->joined_at ?? date('Y-m-d H:i:s'),
                'deleted_at'           => $this->deleted_at
            ]);
            return $this->id;
        }

        public static function getParticipants($where = null, $order = null, $limit = null, $fields = "*") {
            return (new Database('conversation_participants'))->select($where, $order, $limit, $fields);
        }

        public static function getParticipantById($id) {
            return self::getParticipants('id = ' . $id)->fetchObject(self::class);
        }

        public function actualizar() {
            return (new Database('conversation_participants'))->update('id = ' . $this->id, [
                'conversation_id'      => $this->conversation_id,
                'user_id'              => $this->user_id,
                'role'                 => $this->role,
                'last_read_message_id' => $this->last_read_message_id,
                'joined_at'            => $this->joined_at,
                'deleted_at'           => $this->deleted_at
            ]);
        }
    }
