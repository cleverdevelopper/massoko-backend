<?php
namespace App\Model\Entity\Apps;

use App\DatabaseManager\Database;

class DeletedMessagesEntity
{
    public $id;
    public $message_id;
    public $deleted_by;
    public $deleted_at;

    public function cadastrar() {
        $this->id = (new Database('deleted_messages'))->insert([
            'message_id' => $this->message_id,
            'deleted_by' => $this->deleted_by,
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
        return $this->id;
    }

    public static function getDeletedMessages($where = null, $order = null, $limit = null, $fields = "*") {
        return (new Database('deleted_messages'))->select($where, $order, $limit, $fields);
    }
}
