<?php
namespace App\Model\Entity\Apps;

use App\DatabaseManager\Database;

class MessageEditsEntity
{
    public $id;
    public $message_id;
    public $previous_content;
    public $edited_at;

    public function cadastrar() {
        $this->id = (new Database('message_edits'))->insert([
            'message_id'       => $this->message_id,
            'previous_content' => $this->previous_content,
            'edited_at'        => date('Y-m-d H:i:s')
        ]);
        return $this->id;
    }

    public static function getEdits($where = null, $order = null, $limit = null, $fields = "*") {
        return (new Database('message_edits'))->select($where, $order, $limit, $fields);
    }

    public static function getEditsByMessageId($messageId) {
        return self::getEdits('message_id = ' . (int)$messageId, 'edited_at DESC');
    }
}
