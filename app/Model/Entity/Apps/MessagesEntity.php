<?php
    namespace App\Model\Entity\Apps;
    use App\DatabaseManager\Database;

    class MessagesEntity {
        public $id;
        public $conversation_id;
        public $sender_id;
        public $encrypted_content;
        public $signal_message_type;
        public $message_type;
        public $reply_to_message_id;
        public $edited;
        public $sent_at;
        public $created_at;
        public $updated_at;
        public $deleted_at;

        public function cadastrar() {
            $this->id = (new Database('messages'))->insert([
                'conversation_id'   => $this->conversation_id,
                'sender_id'         => $this->sender_id,
                'encrypted_content'   => $this->encrypted_content,
                'signal_message_type' => $this->signal_message_type,
                'message_type'        => $this->message_type,
                'reply_to_message_id' => $this->reply_to_message_id,
                'edited'              => $this->edited ?? 0,
                'sent_at'             => $this->sent_at,
                'deleted_at'          => $this->deleted_at
            ]);
            return $this->id;
        }

        public static function getMessages($where = null, $order = null, $limit = null, $fields = "*") {
            return (new Database('messages'))->select($where, $order, $limit, $fields);
        }

        public static function getMessageById($id) {
            return self::getMessages('id = ' . $id)->fetchObject(self::class);
        }

        public function actualizar() {
            return (new Database('messages'))->update('id = ' . $this->id, [
                'conversation_id'   => $this->conversation_id,
                'sender_id'         => $this->sender_id,
                'encrypted_content'   => $this->encrypted_content,
                'signal_message_type' => $this->signal_message_type,
                'message_type'        => $this->message_type,
                'reply_to_message_id' => $this->reply_to_message_id,
                'edited'              => $this->edited,
                'sent_at'             => $this->sent_at,
                'deleted_at'          => $this->deleted_at
            ]);
        }
    }
