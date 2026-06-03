<?php
namespace App\Model\Entity\Apps;

use App\DatabaseManager\Database;

class MessageReactionsEntity
{
    public $id;
    public $message_id;
    public $user_id;
    public $reaction;
    public $created_at;

    public function cadastrar() {
        $db = new Database('message_reactions');
        $exists = $db->select('message_id = ' . (int)$this->message_id . ' AND user_id = ' . (int)$this->user_id)->fetchObject(self::class);
        if ($exists) {
            $db->update('id = ' . $exists->id, [
                'reaction'   => $this->reaction,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->id = $exists->id;
            return $this->id;
        }

        $this->id = $db->insert([
            'message_id' => $this->message_id,
            'user_id'    => $this->user_id,
            'reaction'   => $this->reaction,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return $this->id;
    }

    public static function getReactions($where = null, $order = null, $limit = null, $fields = "*") {
        return (new Database('message_reactions'))->select($where, $order, $limit, $fields);
    }

    public static function getReactionsByMessageId($messageId) {
        return self::getReactions('message_id = ' . (int)$messageId);
    }

    public function remover() {
        return (new Database('message_reactions'))->delete('id = ' . (int)$this->id);
    }

    public static function deleteReaction($messageId, $userId) {
        return (new Database('message_reactions'))->delete('message_id = ' . (int)$messageId . ' AND user_id = ' . (int)$userId);
    }
}
