<?php
namespace App\Model\Entity\Apps;

use App\DatabaseManager\Database;

class GroupSenderKeysEntity
{
    public $id;
    public $conversation_id;
    public $user_device_id;
    public $sender_key;
    public $created_at;

    public function cadastrar() {
        $this->id = (new Database('group_sender_keys'))->insert([
            'conversation_id' => $this->conversation_id,
            'user_device_id'  => $this->user_device_id,
            'sender_key'      => $this->sender_key,
            'created_at'      => date('Y-m-d H:i:s')
        ]);
        return $this->id;
    }

    public static function getKeys($where = null, $order = null, $limit = null, $fields = "*") {
        return (new Database('group_sender_keys'))->select($where, $order, $limit, $fields);
    }

    public static function getKeyById($id) {
        return self::getKeys('id = ' . (int)$id)->fetchObject(self::class);
    }
}
