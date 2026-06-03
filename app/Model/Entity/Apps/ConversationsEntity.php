<?php
    namespace App\Model\Entity\Apps;
    use App\DatabaseManager\Database;

    class ConversationsEntity {
        public $id;
        public $type;
        public $title;
        public $avatar;
        public $created_by;
        public $last_message_id;
        public $last_message_at;
        public $created_at;
        public $updated_at;
        public $deleted_at;

        public function cadastrar() {
            $this->id = (new Database('conversations'))->insert([
                'type'              => $this->type,
                'title'             => $this->title,
                'avatar'            => $this->avatar,
                'created_by'        => $this->created_by,
                'last_message_id'   => $this->last_message_id,
                'last_message_at'   => $this->last_message_at,
                'deleted_at'        => $this->deleted_at
            ]);
            return $this->id;
        }

        public static function getConversations($where = null, $order = null, $limit = null, $fields = "*") {
            return (new Database('conversations'))->select($where, $order, $limit, $fields);
        }

        public static function getConversationById($id) {
            return self::getConversations('id = ' . $id)->fetchObject(self::class);
        }

        public function actualizar() {
            return (new Database('conversations'))->update('id = ' . $this->id, [
                'type'              => $this->type,
                'title'             => $this->title,
                'avatar'            => $this->avatar,
                'created_by'        => $this->created_by,
                'last_message_id'   => $this->last_message_id,
                'last_message_at'   => $this->last_message_at,
                'deleted_at'        => $this->deleted_at
            ]);
        }
    }
